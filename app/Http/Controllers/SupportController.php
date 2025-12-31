<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    /**
     * Show the support page with tickets list
     */
    public function index()
    {
        $tickets = auth()->user()->supportTickets()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('pages.destek', compact('tickets'));
    }

    /**
     * Store a new support ticket
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:technical,payment,order,integration,feature,other'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'description' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support-attachments', 'public');
        }

        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'attachment_path' => $attachmentPath,
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        // Create initial message
        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['description'],
            'is_staff_reply' => false,
            'attachment_path' => $attachmentPath,
        ]);

        // Send notification email
        $this->sendTicketCreatedEmail($ticket);

        return redirect()
            ->route('destek.show', $ticket)
            ->with('success', 'Destek talebiniz başarıyla oluşturuldu. Talep numaranız: ' . $ticket->ticket_number);
    }

    /**
     * Show a specific ticket
     */
    public function show(SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->load('messages.user');

        return view('pages.destek-detay', compact('ticket'));
    }

    /**
     * Add a reply to a ticket
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$ticket->isOpen()) {
            return back()->with('error', 'Bu talep kapatılmış. Yeni yanıt eklenemez.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support-attachments', 'public');
        }

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_staff_reply' => false,
            'attachment_path' => $attachmentPath,
        ]);

        // Update ticket status
        if ($ticket->status === SupportTicket::STATUS_WAITING_RESPONSE) {
            $ticket->update(['status' => SupportTicket::STATUS_IN_PROGRESS]);
        }

        return back()->with('success', 'Yanıtınız eklendi.');
    }

    /**
     * Close a ticket
     */
    public function close(SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->close(auth()->id());

        return back()->with('success', 'Talep kapatıldı.');
    }

    /**
     * Reopen a ticket
     */
    public function reopen(SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->reopen();

        return back()->with('success', 'Talep yeniden açıldı.');
    }

    /**
     * Send ticket created notification email
     */
    protected function sendTicketCreatedEmail(SupportTicket $ticket): void
    {
        try {
            Mail::send('emails.ticket-created', [
                'ticket' => $ticket,
                'user' => $ticket->user,
            ], function ($message) use ($ticket) {
                $message->to($ticket->user->email, $ticket->user->name)
                    ->subject("Destek Talebi Oluşturuldu - {$ticket->ticket_number}");
            });
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send ticket created email: ' . $e->getMessage());
        }
    }

    /**
     * Send ticket reply notification email
     * Called when staff replies to a user's ticket
     */
    public static function sendTicketReplyEmail(SupportTicket $ticket, TicketMessage $message): void
    {
        try {
            Mail::send('emails.ticket-reply', [
                'ticket' => $ticket,
                'user' => $ticket->user,
                'message' => $message,
            ], function ($mail) use ($ticket) {
                $mail->to($ticket->user->email, $ticket->user->name)
                    ->subject("Destek Talebinize Yanıt Verildi - {$ticket->ticket_number}");
            });
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send ticket reply email: ' . $e->getMessage());
        }
    }
}

