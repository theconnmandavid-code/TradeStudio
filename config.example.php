<?php
// Copy to config.php and fill in. config.php is gitignored — no secrets in the repo.
// With no config.php present, lead.php still records every lead to leads.jsonl
// and reports success, so the form is fully testable locally.

return [
    // Where lead emails go.
    'to_email' => 'you@example.com',

    // Text notifications.
    //
    // Free route — carrier email-to-SMS gateway. No account, no per-message cost.
    // Put your number + your carrier's gateway domain, e.g.:
    //   Verizon  5551234567@vtext.com
    //   AT&T     5551234567@txt.att.net
    //   T-Mobile 5551234567@tmomail.net
    // Carriers throttle these and can drop them; fine for "you have a lead",
    // not something to depend on for anything critical.
    //
    // Leave empty to skip texts.
    'to_sms' => '',

    // From address. Use a real address at the site's own domain once hosted —
    // a From that doesn't match the sending domain is what lands mail in spam.
    'from_email' => 'leads@example.com',
    'from_name'  => 'Trade Studio site',

    // Max submissions per IP per hour. Crude, but stops casual bot floods.
    'rate_limit' => 8,
];
