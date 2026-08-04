<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Success response for a modal (AJAX) form, falling back to a redirect
     * for a normal, non-JS submit.
     */
    protected function modalOk(Request $request, string $route, string $message)
    {
        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return redirect()->route($route)->with('status', $message);
    }

    /**
     * Non-validation error (business rule) response for a modal (AJAX) form.
     */
    protected function modalError(Request $request, string $route, string $message)
    {
        if ($request->ajax()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return redirect()->route($route)->with('error', $message);
    }
}
