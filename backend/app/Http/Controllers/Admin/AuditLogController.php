<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        // Filter by Admin (Actor)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by Action
        if ($request->filled('action')) {
            $query->where('action', $request->action); // Exact match often better for select
        }
        
        // Filter by Target Model
        if ($request->filled('model_type')) {
            $query->where('model_type', 'like', "%{$request->model_type}%");
        }
        
        // Filter by Target ID
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        $logs = $query->latest()->paginate(20)->withQueryString();
        
        // Get list of users who have performed actions (admins)
        // This is a simple way to get filter options without guessing roles
        $adminIds = AuditLog::distinct('user_id')->pluck('user_id');
        $admins = User::whereIn('id', $adminIds)->get();

        // Get distinct actions for filter
        $actions = AuditLog::distinct('action')->pluck('action');

        return view('admin.audit-logs.index', compact('logs', 'admins', 'actions'));
    }
}
