<?php
putenv("LC_ALL=pt_BR.UTF-8");
setlocale(LC_ALL, "pt_BR.UTF-8");
bindtextdomain("messages", "/var/www/libronia.local/locales");
textdomain("messages");

echo _("Libronia - Tu biblioteca digital");