<?php

    $API  = new PerchAPI(1.0, 'pipit_emails');
    

    if(!strpos($_SERVER['REQUEST_URI'], 'pipit_emails') !== false) {
        $Emails = new PipitEmails_Emails($API);
        $emails = $Emails->all();
        
        $API_Email = $API->get('Email');
        $event_handlers = include(PerchUtil::file_path(PERCH_PATH . '/config/pipit_emails_event_handlers.php'));

        foreach($emails as $Email) {
            if($Email->event()) {
                $API->on($Email->event(), function(PerchSystemEvent $Event) use($Email, $API_Email, $event_handlers){
                    // event linked to an email was fired
                    //PerchUtil::debug($Event);
                    //PerchUtil::debug($Email);
                    

                    $sender_name = PERCH_EMAIL_FROM_NAME;
                    $sender_email = PERCH_EMAIL_FROM;
                    if($Email->sender_name()) $sender_name = $Email->sender_name();
                    if($Email->sender_email()) $sender_email = $Email->sender_email();

                    $API_Email->senderName($sender_name);
                    $API_Email->senderEmail($sender_email);
                    $API_Email->replyToEmail($sender_email);
                    $API_Email->subject($Email->subject());

                    // get recepient email address(es)
                    if($Email->from_event() == 1) {
                        $recipient = '';
                        foreach($event_handlers as $key => $handler) {
                            if($key == $Event->event) {
                                $recipient = $handler($Event);
                                //PerchUtil::debug($recipient);
                            }
                        }

                        if(isset($recipient)) {
                            $API_Email->recipientEmail($recipient);
                        }

                    } else {
                        $API_Email->recipientEmail(explode(',', $Email->recipients()));
                    }


                    if($Email->template() != '') {
                        $API_Email->set_template('emails/'.$Email->template());
                        $API_Email->template_method('perch');
                        $API_Email->set_bulk($Email->to_array());
                    }
                    
                    
                    //PerchUtil::debug($API_Email);
                    $API_Email->send();
                });
            }
        }
    }

    