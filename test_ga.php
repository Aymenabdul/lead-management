<?php
require_once 'lib/GoogleAuthenticator.php';

$gauth = new GoogleAuthenticator();
$secret = $gauth->createSecret();
echo "Secret: " . $secret . "\n";

$code = $gauth->getCode($secret);
echo "Code: " . $code . "\n";

$verifyRight = $gauth->verifyCode($secret, $code);
echo "Verify Correct Code: " . ($verifyRight ? "TRUE" : "FALSE") . "\n";

$verifyWrong = $gauth->verifyCode($secret, "000000");
echo "Verify Wrong Code (000000): " . ($verifyWrong ? "TRUE" : "FALSE") . "\n";

// Test with previous code (discrepancy)
// We can't easily simulate time travel without mocking time() in the lib.
?>