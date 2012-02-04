<?
    Include("Includes/global.inc.php");

    // mattd: New and improved session destructionation
    $_SESSION = array();
    
    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (isset($_COOKIE[session_name()])) {
       setcookie(session_name(), '', time()-42000, '/');
    }
    
    // Finally, destroy the session.
    session_destroy();
        
    writeHeader($progText624, $altWindowWidth);

    echo $progText625;

    writeFooter();
?>
