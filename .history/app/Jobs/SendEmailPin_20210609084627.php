<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\SendInvite;
use Mail;

class SendEmailPin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $invite;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email,$invite)
    {
        $this->email = $email;
        $this->invite = $invite;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $invitation = new SendInvite($this->invite);
        Mail::to($this->email)->send($invitation);
    }
}
