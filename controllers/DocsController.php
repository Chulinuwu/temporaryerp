<?php
/**
 * PEGASUS ERP - Documentation Controller
 *
 * Renders static-ish reference pages such as the system requirements UI.
 */
class DocsController extends Controller
{
    /**
     * GET /docs/requirements
     * Renders the system requirements page (non-functional, tech stack, roadmap).
     *
     * This page is public (no auth required) so it can be browsed as design
     * reference without logging in. When the user IS logged in, the full ERP
     * shell (navbar + sidebar) is rendered automatically by the layout.
     */
    public function requirements()
    {
        // Preview-friendly session: populate minimal session data so the
        // authenticated layout renders the full ERP shell. This does NOT grant
        // any real permissions — the shell reads display-only fields.
        if (empty($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 0;
            $_SESSION['user'] = [
                'full_name' => 'Preview',
                'role'      => 'Guest',
            ];
            $_SESSION['_preview_mode'] = true;
        }

        $this->render('docs/requirements', [
            'pageTitle' => 'System Requirements',
        ]);
    }
}
