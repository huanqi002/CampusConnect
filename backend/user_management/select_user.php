<?php
// The former demo profile picker bypassed account authentication. Keep old
// bookmarks working while sending everyone through the email/password login.
header('Location: ../../login.php');
exit;
