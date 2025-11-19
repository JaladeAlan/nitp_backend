<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ContactRequest;
use App\Mail\ContactMail;
use App\Mail\ContactAutoReply;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return ContactMessage::orderBy('created_at', 'desc')->paginate(20);
    }
    
    public function show($id) {
        return ContactMessage::findOrFail($id);
    }

    public function send(ContactRequest $request)
    {
        // Save message to database
        ContactMessage::create([
            'name'    => $request->name,
            'email'   => $request->email,
            'message' => $request->message,
        ]);

        // Send email to admin
        Mail::to('info@nitp-oyo.org')
            ->send(new ContactMail($request->name, $request->email, $request->message));

        // Optional: Auto reply to sender
        Mail::to($request->email)
            ->send(new ContactAutoReply($request->name));

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully!'
        ]);
    }
}
