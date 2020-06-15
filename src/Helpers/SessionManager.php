<?php

declare(strict_types=1);


namespace Bitsnbytes\Helpers;


class SessionManager
{
    private Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Start session and destroy old sessions older than <b>config[session.lifetime]</b> seconds.
     */
    public function start(): void
    {
        session_start();

        // Do not allow to use too old session ID
        if (isset($_SESSION['deleted_time']) && $_SESSION['deleted_time'] !== '' &&
            $_SESSION['deleted_time'] < time() - $this->config->get('session.lifetime')) {
            session_destroy();
            session_start();
        }
    }

    /**
     * Mark previous session as outdated and create a new session with a new id.
     */
    public function regenerate_id(): void
    {
        // Call session_create_id() while session is active to avoid collisions.
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }

        $newid = session_create_id('bitsbytes-');
        // Set deleted timestamp. Session data must not be deleted immediately for reasons.
        $_SESSION['deleted_time'] = time();
        session_commit();

        session_id($newid);

        // Make sure to accept user defined session ID
        ini_set('session.use_strict_mode', '0');
        session_start();
    }
}