@extends('adminlte::page')

@section('title', 'Browser Sessions')

@section('content_header')
    <div class="d-flex align-items-center w-100">
        <h1 class="mb-0">Browser Sessions</h1>
        <div class="ml-auto d-flex align-items-center">
            <div class="btn-group" role="group">
                <button id="manual-refresh" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary">
                    <label class="mb-0 d-flex align-center justify-content-center" style="cursor: pointer; gap: 5px">
                        <input type="checkbox" id="auto-refresh-checkbox"> Auto-refresh (30s)
                    </label>
                </button>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {!! session('error') !!}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title">Active Sessions</h3>
                    <div class="ml-auto">
                        <form action="{{ route('admin.browser-sessions.start') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary">+ Start</button>
                        </form>
                        <form action="{{ route('admin.browser-sessions.stop-all') }}" method="POST" class="d-inline ml-2" id="stop-all-form">
                            @csrf
                            <input type="hidden" name="clean" value="1">
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#confirmStopAllModal">Stop All</button>
                        </form>
                    </div>
                </div>
                <div class="card-body position-relative" id="sessions-wrapper">
                    <div id="sessions-loader" class="overlay d-none">
                        <i class="fas fa-2x fa-sync-alt fa-spin position-absolute text-primary" style="top: 100px; z-index: 2;"></i>
                    </div>
                    <table id="browser-sessions-table" class="table table-bordered table-striped" style="min-height: 130px">
                        <thead>
                        <tr>
                            <th style="width: 80px">Port</th>
                            <th style="width: 120px">PID</th>
                            <th style="width: 220px">User</th>
                            <th>URL</th>
                            <th style="width: 160px">Uptime</th>
                            <th style="width: 120px">Active</th>
                            <th style="width: 60px">Action</th>
                        </tr>
                        </thead>
                        <tbody id="sessions-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    (function() {
        const dataUrl = @json($dataUrl);
        const body = document.getElementById('sessions-body');
        const manualBtn = document.getElementById('manual-refresh');
        const autoCb = document.getElementById('auto-refresh-checkbox');
        const browserSessionsTable = document.getElementById('browser-sessions-table');

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        async function fetchSessions(forceBadgeUpdate = false) {
            const loader = document.getElementById('sessions-loader');
            if (loader) {
                loader.classList.remove('d-none');
                browserSessionsTable.style.opacity = '0.5';
            }

            try {
                const res = await fetch(dataUrl, { cache: 'no-cache' });
                if (!res.ok) return;
                const data = await res.json();
                const sessions = Array.isArray(data.sessions) ? data.sessions : [];
                let html = '';
                for (const s of sessions) {
                    const urlCell = s.url ? `<a href="${escapeHtml(s.url)}" target="_blank" rel="noopener">${escapeHtml(s.url)}</a>` : '-';
                    const activeBadge = s.active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>';
                    const userCell = (s.user && s.user.edit_url)
                        ? (() => {
                            const hasNameOrEmail = !!(s.user.name || s.user.email);
                            const label = hasNameOrEmail
                                ? `${s.user.name || s.user.email} (ID ${s.user.id})`
                                : `ID ${s.user.id}`;
                            return `<a href="${escapeHtml(s.user.edit_url)}" target="_blank" rel="noopener">${escapeHtml(label)}</a>`;
                          })()
                        : '-';
                    const uptimeCell = s.uptime_human ? escapeHtml(s.uptime_human) : '-';
                    const stopByPidForm = (s.active && s.xpra_pid) ? `
                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#confirmStopPidModal${s.xpra_pid}"><i class="fas fa-stop"></i></button>
                        <div class="modal fade" id="confirmStopPidModal${s.xpra_pid}" tabindex="-1" role="dialog" aria-hidden="true">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">Stop Session</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                              </div>
                              <div class="modal-body">Are you sure you want to stop session with PID ${s.xpra_pid}?</div>
                              <div class="modal-footer">
                                <form action="{{ route('admin.browser-sessions.stop-pid') }}" method="POST" class="d-inline">
                                  @csrf
                                  <input type="hidden" name="pid" value="${s.xpra_pid}">
                                  <button type="submit" class="btn btn-danger">Stop</button>
                                </form>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                              </div>
                            </div>
                          </div>
                        </div>
                    ` : '';
                    html += `
                        <tr>
                            <td>${s.port}</td>
                            <td>${s.xpra_pid ?? '-'}</td>
                            <td>${userCell}</td>
                            <td>${urlCell}</td>
                            <td>${uptimeCell}</td>
                            <td>${activeBadge}</td>
                            <td>${stopByPidForm}</td>
                        </tr>
                        ${s.active && s.xpra_pid ? `
                        <div class="modal fade" id="confirmStopPidModal${s.xpra_pid}" tabindex="-1" role="dialog" aria-hidden="true">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">Stop Session</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                              </div>
                              <div class="modal-body">Are you sure you want to stop session with PID ${s.xpra_pid}?</div>
                              <div class="modal-footer">
                                <form action="{{ route('admin.browser-sessions.stop-pid') }}" method="POST" class="d-inline">
                                  @csrf
                                  <input type="hidden" name="pid" value="${s.xpra_pid}">
                                  <button type="submit" class="btn btn-danger">Stop</button>
                                </form>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                              </div>
                            </div>
                          </div>
                        </div>
                        ` : ''}
                    `;
                }
                body.innerHTML = html;
                // Backend renders the sidebar badge; no JS badge updates here
            } catch (_) {}
            finally {
                if (loader) {
                    loader.classList.add('d-none');
                    browserSessionsTable.style.opacity = '1';
                }
            }
        }

        const KEY = 'browserSessionsAutoRefresh';
        let timer = null;
        let isModalOpen = false;
        // No JS-based sidebar badge; count is rendered on backend

        function setTimer(enabled) {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
            if (enabled) {
                timer = setInterval(function(){
                    if (!isModalOpen) {
                        fetchSessions();
                    }
                }, 30000);
            }
        }

        function initAutoRefresh() {
            const saved = localStorage.getItem(KEY);
            const enabled = saved === '1';
            autoCb.checked = enabled;
            setTimer(enabled);
        }

        autoCb.addEventListener('change', function() {
            const enabled = !!this.checked;
            localStorage.setItem(KEY, enabled ? '1' : '0');
            setTimer(enabled);
            if (enabled) fetchSessions();
        });

        manualBtn && manualBtn.addEventListener('click', function(e){ e.preventDefault(); fetchSessions(true); });

        // Bootstrap modal submit for Stop All (use delegation to ensure listener exists even if modal is after script)
        (function(){
            const form = document.getElementById('stop-all-form');
            if (!form) return;
            document.addEventListener('click', function(e){
                const isConfirmBtn = (e.target && (e.target.id === 'confirmStopAllSubmit' || (e.target.closest && e.target.closest('#confirmStopAllSubmit'))));
                if (isConfirmBtn) {
                    e.preventDefault();
                    form.submit();
                }
            });
        })();

        initAutoRefresh();
        fetchSessions(true);

        // Pause auto-refresh while any Bootstrap modal is open
        if (window.$ && $.fn && $.fn.modal) {
            $(document).on('shown.bs.modal', function(){ isModalOpen = true; });
            $(document).on('hidden.bs.modal', function(){ isModalOpen = false; });
        }
    })();
</script>

<!-- Confirm Stop All Modal -->
<div class="modal fade" id="confirmStopAllModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Stop All Sessions</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">This will stop all sessions and clean profiles. Continue?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="confirmStopAllSubmit">Stop All</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
 </div>
@stop



