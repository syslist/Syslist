<?
    Function getExtraCommentSQLForStuckUsers(&$extraSQLSystem, &$extraSQLUser, &$extraSQLSubjectless) {
        // Yes, I realize we could condense this, but it's clearer when the SQL is all written out
        // (Let me know if you disagree)

        // System tickets check hardware subject, author, and assignedUser
        $extraSQLSystem = "((c.commentLocationID=" . $_SESSION['locationStatus'] . ") OR ";
        $extraSQLSystem .= "(c.commentLocationID IS NULL AND ((h.locationID=" . $_SESSION['locationStatus'] . ") OR ";
        $extraSQLSystem .= "(s.userLocationID=" . $_SESSION['locationStatus'] .") OR ";
        $extraSQLSystem .= "(s2.userLocationID=" . $_SESSION['locationStatus'] . ")))) AND ";

        // Users tickets check user subject, author, and assignedUser
        $extraSQLUser = "((c.commentLocationID=" . $_SESSION['locationStatus'] . ") OR ";
        $extraSQLUser .= "(c.commentLocationID IS NULL AND ((s3.userLocationID=" . $_SESSION['locationStatus'] . ") OR ";
        $extraSQLUser .= "(s.userLocationID=" . $_SESSION['locationStatus'] .") OR ";
        $extraSQLUser .= "(s2.userLocationID=" . $_SESSION['locationStatus'] . ")))) AND ";

        // Subjectless tickets check author and assignedUser
        $extraSQLSubjectless = "((c.commentLocationID=" . $_SESSION['locationStatus'] . ") OR ";
        $extraSQLSubjectless .= "(c.commentLocationID IS NULL AND ";
        $extraSQLSubjectless .= "((s.userLocationID=" . $_SESSION['locationStatus'] .") OR ";
        $extraSQLSubjectless .= "(s2.userLocationID=" . $_SESSION['locationStatus'] . ")))) AND ";     
    }
    
    Function getCommentSQLForSystems($extraSQLSystem) {
        return "SELECT s.firstName, s.middleInit, s.lastName, c.*, h.hardwareStatus, h.ipAddress,
            t.visDescription, t.visManufacturer, o.categoryName
            FROM (comments as c, tblSecurity as s, hardware as h, hardware_types as t, tblSecurity as s2)
            LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID 
            WHERE $extraSQLSystem c.assignedUserID=s.id AND c.subjectType='h' AND c.authorID=s2.id 
            AND c.commentStatus IS NOT NULL AND c.subjectID=h.hardwareID AND h.hardwareTypeID=t.hardwareTypeID
            AND c.accountID=" . $_SESSION['accountID'] . ""; 
    }
    
    Function getCommentSQLForUsers($extraSQLUser) {
        return "SELECT s.firstName, s.middleInit, s.lastName, c.*, o.categoryName
            FROM (comments as c, tblSecurity as s)
            LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID LEFT JOIN tblSecurity as s2 ON c.authorID=s2.id LEFT JOIN tblSecurity as s3 ON c.subjectID=s3.id 
            WHERE $extraSQLUser c.assignedUserID=s.id AND c.subjectType='u' AND
            c.commentStatus IS NOT NULL AND c.accountID=" . $_SESSION['accountID'] . "";
    }
    
     Function getCommentSQLForSubjectless($extraSQLSubjectless) {
        return  "SELECT s.firstName, s.middleInit, s.lastName, c.*, o.categoryName
            FROM (comments as c, tblSecurity as s)
            LEFT JOIN commentCategories as o ON o.categoryID=c.categoryID LEFT JOIN tblSecurity as s2 ON c.authorID=s2.id
            WHERE $extraSQLSubjectless c.assignedUserID=s.id AND c.subjectType IS NULL AND
            c.commentStatus IS NOT NULL AND c.accountID=" . $_SESSION['accountID'] . "";
    }
    
?>