<?php

class ErrorMessages {

    private static $messages = [
        'INVALID_GET' => 'Invalid request. Please try again.',
        'INVALID_ID' => 'Invalid ID. The requested item could not be found.',
        'WRONG_PASS' => 'Incorrect password. Check your Hacked Database for the correct credentials.',
        'NO_PERMISSION' => 'You do not have permission to perform this action.',
        'NO_INSTALLER' => 'Cannot find the doom installer. Make sure it is available on your system.',
        'NO_COLLECTOR' => 'You do not have a virus collector. Research or download one first.',
        'DOWNGRADE' => 'Hardware downgrade is not possible. You can only upgrade components.',
        'NOT_LISTED' => 'This IP is not listed on your Hacked Database. Hack and login to a server to add it.',
        'INEXISTENT_SERVER' => 'This server does not exist. Double-check the IP address.',
        'INEXISTENT_IP' => 'This IP does not exist. Double-check the address and try again.',
        'INVALID_IP' => 'This IP address is invalid. Use the format X.X.X.X.',
        'BAD_XHD' => 'Your external hard drive does not have enough space for this software.',
        'BAD_MONEY' => 'You don\'t have enough money for this action. Collect virus earnings or complete missions for more.',
        'NO_LICENSE' => 'You do not have the license to research this software. Check University for available licenses.',
        'NO_CERTIFICATION' => 'You need a certification to access this feature. Visit University > Certifications.',
        'NOT_INSTALLED' => 'This software is not installed. Install it from your Software page first.',
        'CANT_UINSTALL' => 'You cannot uninstall this software. Some core software must remain installed.',
        'INSUFFICIENT_RAM' => 'Not enough RAM to complete this action. Stop some running software or upgrade your hardware.',
        'ALREADY_INSTALLED' => 'This software is already installed.',
        'NOT_EXECUTABLE' => 'This software is not executable. Only certain software types can be run.',
        'VIRUS_ALREADY_INSTALLED' => 'You already have a virus of this type installed on this computer.',
        'NO_SEEKER' => 'You don\'t have a seeker. Research or download one to find hidden files.',
        'NO_HAVE_SOFT' => 'You do not have this software. Download it first.',
        'SOFT_HIDDEN' => 'This software is hidden. Use a seeker to reveal it.',
        'SOFT_ALREADY_HAVE' => 'You already have this software.',
        'INEXISTENT_SOFTWARE' => 'This software does not exist.',
        'CANT_DELETE' => 'You cannot delete this software. Some files are protected.',
        'ALREADY_LISTED' => 'You already have this IP on your Hacked Database.',
        'NO_SOFT' => 'You don\'t have the required software installed. Visit the Download Center to get one.',
        'NO_NMAP_VICTIM' => 'The remote server does not have NMAP software installed.',
        'BAD_ACC' => 'Invalid bank account number. Please check and try again.',
        'INEXISTENT_ACC' => 'This bank account does not exist.',
        'BAD_BANK' => 'This IP is not a bank server.',
        'AUTO_TRANSFER' => 'You cannot transfer money to yourself.',
        'BANK_NOT_LOGGED' => 'You need to log into a bank account first.',
        'NO_AMOUNT' => 'Please enter an amount greater than zero.',
        'BAD_CRACKER' => 'Your Cracker version is too low. Research a better version to hack this server.',
        'DOWNLOAD_INSUFFICIENT_HD' => 'Not enough hard drive space to download this software. Upgrade your hardware or delete some files.',
        'UPLOAD_INSUFFICIENT_HD' => 'Not enough disk space on the remote server to upload this software.',
        'UPLOAD_SOFT_ALREADY_HAVE' => 'The remote server already has this software.',
        'HIDE_INSTALLED_SOFTWARE' => 'Cannot hide an installed software. Uninstall it first.',
        'BAH_BAD_CRACKER' => 'Access denied: your software cannot crack this bank account. Upgrade your cracker.',
        'BAH_ALREADY_CRACKED' => 'You already have this bank account listed.',
        'HXP_BAD_EXP' => 'Access denied: your exploit version is too low. Research a better one.',
        'HXP_NO_EXP' => 'You do not have any running exploits. Install one from your Software page.',
        'NO_FTP_EXP' => 'You do not have a running FTP exploit. Install one first.',
        'NO_SSH_EXP' => 'You do not have a running SSH exploit. Install one first.',
        'HXP_NO_SCAN' => 'You do not have any running scanner. Install a port scanner first.',
        'IPRESET_NO_TIME' => 'You cannot reset your IP right now. Please wait for the cooldown period.',
        'PWDRESET_NO_TIME' => 'You cannot change your password right now. Please wait for the cooldown period.',
        'DOOM_CLAN_ONLY' => 'Doom can only be installed on your clan server.',
        'FOLDER_INEXISTENT_SOFTWARE' => 'This software is not in this folder.',
        'FOLDER_ALREADY_HAVE' => 'This software is already in a folder.',
        'FOLDER_INEXISTENT' => 'This folder does not exist.',
        'PROC_NOT_FOUND' => 'Process not found. It may have already completed.',
        'PROC_ALREADY_PAUSED' => 'This process is already paused.',
        'PROC_NOT_PAUSED' => 'This process is not paused.',
        'PROC_ALREADY_COMPLETED' => 'This process has already completed.',
        'NO_SPACE' => 'Not enough hard drive space. Upgrade your hardware or delete some files.',
        'NO_RAM' => 'Not enough RAM. Stop some running software or upgrade your hardware.',
        'NO_MONEY' => 'You don\'t have enough money for this action.',
        'FORUM_EXISTS' => 'A clan forum already exists.',
    ];

    /**
     * Get user-friendly message for an error code.
     * Returns the original code if no mapping exists.
     */
    public static function get($code) {
        return self::$messages[$code] ?? $code;
    }

}

?>
