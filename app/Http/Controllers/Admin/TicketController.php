<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Controllers\Traits\VendorWalletTrait;
use App\Models\Module;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Auth;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;

class TicketController extends Controller
{
    use MediaUploadingTrait, VendorWalletTrait;

    public function index(Request $request)
    {
        if (! Schema::hasTable('support_tickets')) {
            $moduleName = 'Support';
            $statusCounts = ['all' => 0, 'open' => 0, 'closed' => 0];
            $data = new LengthAwarePaginator([], 0, 50, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('admin.ticket.index', compact('data', 'moduleName', 'statusCounts'));
        }

        $moduleId = null;
        $moduleName = 'Support';

        if (Schema::hasTable('module')) {
            $module = Module::where('default_module', '1')->first() ?? Module::query()->first();
            if ($module) {
                $moduleId = $module->id;
                $moduleName = $module->name ?? $moduleName;
            }
        }

        $status = $request->input('status');
        $query = SupportTicket::query()->with(['appUser:id,first_name,last_name'])->orderByDesc('id');

        if ($moduleId !== null && Schema::hasColumn('support_tickets', 'module')) {
            $query->where('module', $moduleId);
        }

        if (in_array((string) $status, ['0', '1'], true)) {
            $query->where('support_tickets.thread_status', (int) $status);
        }

        $countsQuery = SupportTicket::query();
        if ($moduleId !== null && Schema::hasColumn('support_tickets', 'module')) {
            $countsQuery->where('module', $moduleId);
        }

        $statusCounts = [
            'all' => (clone $countsQuery)->count(),
            'open' => (clone $countsQuery)->where('thread_status', 1)->count(),
            'closed' => (clone $countsQuery)->where('thread_status', 0)->count(),
        ];

        $data = $query->paginate(50);

        return view('admin.ticket.index', compact('data', 'moduleName', 'statusCounts'));
    }

    public function reply(Request $request, $id)
    {
        if (! Schema::hasTable('support_tickets')) {
            abort(404);
        }

        $data = SupportTicket::with(['replies.AppUser'])
            ->where('id', $id)
            ->firstOrFail();

        $replies = $data->replies()->orderBy('id', 'desc')->paginate(50);

        return view('admin.ticket.ticketmessage', compact('data', 'replies'));
    }

    public function threads(Request $request, $id)
    {
        if (! Schema::hasTable('support_tickets')) {
            abort(404);
        }

        $userId = Auth::id();
        $adminedata = $userId ? User::find($userId) : null;

        $supportTicketData = SupportTicket::where('id', $id)->first();

        $supportTicketReplies = SupportTicket::with(['appUser', 'replies' => function ($query) {
            $query->orderBy('id', 'desc');
        }])->findOrFail($id);

        return view('admin.ticket.thread', compact('id', 'adminedata', 'supportTicketData', 'supportTicketReplies'));
    }

    public function create(Request $request, $id)
    {
        if (! Schema::hasTable('support_tickets') || ! Schema::hasTable('support_ticket_replies')) {
            return redirect()->route('admin.ticket.index')->with('error', 'Support ticket tables are not ready.');
        }

        $status = 1;
        $admin = 1;
        $userId = Auth::id();
        if (! $userId) {
            return redirect()->route('login');
        }

        $add = new SupportTicketReply;
        $add->thread_id = $id;
        $add->user_id = $userId;
        $add->is_admin_reply = $admin;
        $add->message = $request->message;
        $add->reply_status = $status;
        $add->save();

        $ticket = SupportTicket::where('id', $id)->first();
        if (! $ticket) {
            return redirect()->route('admin.ticket.index');
        }

        $templateId = 42;
        try {
            $this->sendNotificationOnTicketReply($id, $ticket->user_id, $ticket->title, $templateId);
        } catch (\Throwable $e) {
            Log::warning('Ticket reply notification failed', [
                'ticket_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.ticket.thread', $id);

    }

    public function destroy($id)
    {
        try {
            if (! Schema::hasTable('support_tickets')) {
                return response()->json(['message' => 'Support ticket table not found.'], 500);
            }
            $ticket = SupportTicket::findOrFail($id);
            $ticket->delete();

            return response()->json(['message' => 'Ticket deleted successfully.']);
        } catch (\Exception $e) {

            return response()->json(['message' => 'Error deleting ticket.'], 500);
        }
    }

    public function ticketDeleteAll(Request $request) //
    {
        abort_if(Gate::denies('ticket_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $ids = $request->input('ids');

        if (! empty($ids)) {
            try {
                SupportTicket::whereIn('id', $ids)->delete();

                return response()->json(['message' => 'Items deleted successfully'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Something went wrong'], 500);
            }
        }

    }
}
