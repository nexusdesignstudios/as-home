@extends('layouts.main')

@section('title')
    {{ __('Property Edit Requests') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"> </div>
        </div>
    </div>
@endsection

@section('css')
<style>
    .edit-requests-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 0.5rem 0.5rem 0 0;
        margin: -1.5rem -1.5rem 1.5rem -1.5rem;
    }
    .filter-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }
    .filter-tab {
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 2px solid transparent;
    }
    .filter-tab:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .filter-tab.active {
        border-color: currentColor;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .filter-tab.pending.active {
        background: #ffc107;
        color: #000;
    }
    .filter-tab.approved.active {
        background: #28a745;
        color: #fff;
    }
    .filter-tab.rejected.active {
        background: #dc3545;
        color: #fff;
    }
    .filter-tab.all.active {
        background: #6c757d;
        color: #fff;
    }
    .count-badge {
        background: rgba(255,255,255,0.3);
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .filter-tab.active .count-badge {
        background: rgba(0,0,0,0.2);
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 1rem;
        margin: 2rem 0;
    }
    .empty-state-icon {
        font-size: 4rem;
        color: #6c757d;
        margin-bottom: 1rem;
    }
    .empty-state h4 {
        color: #495057;
        margin-bottom: 0.5rem;
    }
    .empty-state p {
        color: #6c757d;
        margin: 0;
    }
    .table-enhanced {
        border-collapse: separate;
        border-spacing: 0;
    }
    .table-enhanced thead th {
        background: #f8f9fa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem;
        border-bottom: 2px solid #dee2e6;
    }
    .table-enhanced tbody tr {
        transition: all 0.2s ease;
    }
    .table-enhanced tbody tr:hover {
        background: #f8f9fa;
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .table-enhanced tbody td {
        padding: 1rem;
        vertical-align: middle;
    }
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
        display: inline-block;
        font-size: 0.875rem;
    }
    .property-link {
        color: #667eea;
        font-weight: 500;
        text-decoration: none;
        transition: color 0.2s;
    }
    .property-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }
    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
</style>
@endsection

@section('content')
    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="edit-requests-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1">
                            <i class="bi bi-file-earmark-text me-2"></i>
                            {{ __('Property Edit Requests') }}
                        </h4>
                        <p class="mb-0 opacity-75">{{ __('Review and manage property edit requests from owners') }}</p>
                    </div>
                </div>
                
                <div class="filter-tabs mt-4">
                    @php
                        try {
                            $pendingCount = \App\Models\PropertyEditRequest::where('status', 'pending')->count();
                            $approvedCount = \App\Models\PropertyEditRequest::where('status', 'approved')->count();
                            $rejectedCount = \App\Models\PropertyEditRequest::where('status', 'rejected')->count();
                        } catch (\Exception $e) {
                            $pendingCount = $approvedCount = $rejectedCount = 0;
                        }
                    @endphp
                    
                    <a href="{{ route('property-edit-requests.index', ['status' => 'pending']) }}" 
                       class="filter-tab pending {{ $status == 'pending' ? 'active' : '' }}"
                       style="background: {{ $status == 'pending' ? '#ffc107' : 'rgba(255,255,255,0.1)' }}; color: {{ $status == 'pending' ? '#000' : '#fff' }};">
                        <i class="bi bi-clock-history"></i>
                        <span>{{ __('Pending') }}</span>
                        <span class="count-badge">{{ $pendingCount }}</span>
                    </a>
                    
                    <a href="{{ route('property-edit-requests.index', ['status' => 'approved']) }}" 
                       class="filter-tab approved {{ $status == 'approved' ? 'active' : '' }}"
                       style="background: {{ $status == 'approved' ? '#28a745' : 'rgba(255,255,255,0.1)' }}; color: #fff;">
                        <i class="bi bi-check-circle"></i>
                        <span>{{ __('Approved') }}</span>
                        <span class="count-badge">{{ $approvedCount }}</span>
                    </a>
                    
                    <a href="{{ route('property-edit-requests.index', ['status' => 'rejected']) }}" 
                       class="filter-tab rejected {{ $status == 'rejected' ? 'active' : '' }}"
                       style="background: {{ $status == 'rejected' ? '#dc3545' : 'rgba(255,255,255,0.1)' }}; color: #fff;">
                        <i class="bi bi-x-circle"></i>
                        <span>{{ __('Rejected') }}</span>
                        <span class="count-badge">{{ $rejectedCount }}</span>
                    </a>
                    
                    <a href="{{ route('property-edit-requests.index', ['status' => 'all']) }}" 
                       class="filter-tab all {{ $status == 'all' ? 'active' : '' }}"
                       style="background: {{ $status == 'all' ? '#6c757d' : 'rgba(255,255,255,0.1)' }}; color: #fff;">
                        <i class="bi bi-list-ul"></i>
                        <span>{{ __('All') }}</span>
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                @if(isset($error))
                    <div class="alert alert-danger border-0 shadow-sm">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div>
                                <strong>{{ __('Error') }}</strong>
                                <p class="mb-0">{{ $error }}</p>
                                @if(strpos($error, 'table does not exist') !== false)
                                    <hr>
                                    <strong>{{ __('To fix this:') }}</strong>
                                    <ol class="mb-0">
                                        <li>{{ __('Run the migration:') }} <code>php artisan migrate</code></li>
                                        <li>{{ __('Or manually create the table using the migration file') }}</li>
                                    </ol>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($editRequests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-enhanced">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">{{ __('ID') }}</th>
                                    <th>{{ __('Property') }}</th>
                                    <th>{{ __('Owner') }}</th>
                                    <th style="width: 180px;">{{ __('Requested At') }}</th>
                                    <th style="width: 150px;">{{ __('Status') }}</th>
                                    <th style="width: 150px;">{{ __('Changes') }}</th>
                                    <th style="width: 120px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($editRequests as $request)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">#{{ $request->id }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('property.edit', $request->property_id) }}" 
                                               target="_blank" 
                                               class="property-link">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                                {{ $request->property->title ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <strong>{{ $request->requestedBy->name ?? 'N/A' }}</strong>
                                                <small class="text-muted">
                                                    <i class="bi bi-envelope me-1"></i>
                                                    {{ $request->requestedBy->email ?? '' }}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span>{{ $request->created_at->format('M d, Y') }}</span>
                                                <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($request->status == 'pending')
                                                <span class="status-badge bg-warning text-dark">
                                                    <i class="bi bi-clock-history me-1"></i>
                                                    {{ __('Pending') }}
                                                </span>
                                            @elseif($request->status == 'approved')
                                                <div class="d-flex flex-column">
                                                    <span class="status-badge bg-success text-white mb-1">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        {{ __('Approved') }}
                                                    </span>
                                                    @if($request->reviewedBy)
                                                        <small class="text-muted">
                                                            <i class="bi bi-person me-1"></i>
                                                            {{ $request->reviewedBy->name }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="d-flex flex-column">
                                                    <span class="status-badge bg-danger text-white mb-1">
                                                        <i class="bi bi-x-circle me-1"></i>
                                                        {{ __('Rejected') }}
                                                    </span>
                                                    @if($request->reviewedBy)
                                                        <small class="text-muted">
                                                            <i class="bi bi-person me-1"></i>
                                                            {{ $request->reviewedBy->name }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $original = $request->original_data ?? [];
                                                $edited = $request->edited_data ?? [];
                                                $changes = [];
                                                foreach ($edited as $key => $value) {
                                                    if (!isset($original[$key]) || $original[$key] != $value) {
                                                        $changes[] = $key;
                                                    }
                                                }
                                            @endphp
                                            <span class="badge bg-info text-white">
                                                <i class="bi bi-pencil-square me-1"></i>
                                                {{ count($changes) }} {{ __('fields') }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm action-btn {{ $request->status == 'pending' ? 'btn-success' : 'btn-info' }}" 
                                                    onclick="viewEditRequest({{ $request->id }})">
                                                <i class="bi bi-eye me-1"></i>
                                                {{ __('View') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h4>{{ __('No Edit Requests Found') }}</h4>
                        <p>{{ __('There are no property edit requests matching your current filter.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- View Edit Request Modal -->
    <div class="modal fade" id="viewEditRequestModal" tabindex="-1" aria-labelledby="viewEditRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="modal-title d-flex align-items-center" id="viewEditRequestModalLabel">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        {{ __('Property Edit Request Details') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="editRequestDetails">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                        <p class="mt-3 text-muted">{{ __('Loading request details...') }}</p>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top" id="editRequestActions">
                    <!-- Actions will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Reason Modal -->
    <div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title d-flex align-items-center" id="rejectReasonModalLabel">
                        <i class="bi bi-x-circle me-2"></i>
                        {{ __('Reject Edit Request') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectForm">
                    <div class="modal-body p-4">
                        <input type="hidden" id="rejectRequestId" name="edit_request_id">
                        <input type="hidden" name="status" value="rejected">
                        <div class="alert alert-warning border-0 mb-4">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>{{ __('Warning:') }}</strong> {{ __('This action cannot be undone. Please provide a clear reason for rejection.') }}
                        </div>
                        <div class="mb-3">
                            <label for="reject_reason" class="form-label fw-bold">
                                {{ __('Rejection Reason') }} 
                                <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" 
                                      id="reject_reason" 
                                      name="reject_reason" 
                                      rows="5" 
                                      required 
                                      placeholder="{{ __('Please provide a detailed reason for rejecting this edit request...') }}"
                                      style="resize: vertical;"></textarea>
                            <small class="form-text text-muted">
                                {{ __('This reason will be visible to the property owner.') }}
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x me-1"></i>
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>
                            {{ __('Reject Request') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @section('script')
    <script>
        function viewEditRequest(requestId) {
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewEditRequestModal'));
            modal.show();
            
            // Load edit request details
            fetch(`{{ url('property-edit-requests') }}/${requestId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error === false) {
                        displayEditRequest(data.data.edit_request);
                    } else {
                        document.getElementById('editRequestDetails').innerHTML = 
                            '<div class="alert alert-danger">' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('editRequestDetails').innerHTML = 
                        '<div class="alert alert-danger">{{ __('Error loading edit request details.') }}</div>';
                });
        }

        function displayEditRequest(request) {
            const original = request.original_data || {};
            const edited = request.edited_data || {};
            const changes = [];
            
            // Find all changes
            const allKeys = new Set([...Object.keys(original), ...Object.keys(edited)]);
            allKeys.forEach(key => {
                if (key !== 'id' && key !== 'created_at' && key !== 'updated_at') {
                    const origValue = original[key];
                    const editValue = edited[key];
                    if (origValue != editValue) {
                        changes.push({
                            field: key,
                            original: origValue,
                            edited: editValue
                        });
                    }
                }
            });

            let html = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light mb-3">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase mb-3">
                                    <i class="bi bi-info-circle me-2"></i>{{ __('Request Information') }}
                                </h6>
                                <div class="mb-2">
                                    <strong><i class="bi bi-house me-2 text-primary"></i>{{ __('Property:') }}</strong>
                                    <div class="mt-1">${request.property ? request.property.title : 'N/A'}</div>
                                </div>
                                <div class="mb-2">
                                    <strong><i class="bi bi-person me-2 text-primary"></i>{{ __('Owner:') }}</strong>
                                    <div class="mt-1">${request.requested_by ? request.requested_by.name : 'N/A'}</div>
                                </div>
                                <div class="mb-2">
                                    <strong><i class="bi bi-calendar me-2 text-primary"></i>{{ __('Requested:') }}</strong>
                                    <div class="mt-1">${new Date(request.created_at).toLocaleString()}</div>
                                </div>
                                <div>
                                    <strong><i class="bi bi-tag me-2 text-primary"></i>{{ __('Status:') }}</strong>
                                    <div class="mt-1">
                                        <span class="badge bg-${request.status == 'pending' ? 'warning text-dark' : (request.status == 'approved' ? 'success' : 'danger')} px-3 py-2">
                                            <i class="bi bi-${request.status == 'pending' ? 'clock-history' : (request.status == 'approved' ? 'check-circle' : 'x-circle')} me-1"></i>
                                            ${request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light mb-3">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase mb-3">
                                    <i class="bi bi-clipboard-data me-2"></i>{{ __('Review Details') }}
                                </h6>
                                <div class="mb-2">
                                    <strong><i class="bi bi-pencil-square me-2 text-info"></i>{{ __('Total Changes:') }}</strong>
                                    <div class="mt-1">
                                        <span class="badge bg-info px-3 py-2">${changes.length} {{ __('fields') }}</span>
                                    </div>
                                </div>
                                ${request.reviewed_by ? `
                                <div class="mb-2">
                                    <strong><i class="bi bi-person-check me-2 text-success"></i>{{ __('Reviewed By:') }}</strong>
                                    <div class="mt-1">${request.reviewed_by.name}</div>
                                </div>
                                ` : ''}
                                ${request.reviewed_at ? `
                                <div class="mb-2">
                                    <strong><i class="bi bi-clock me-2 text-success"></i>{{ __('Reviewed At:') }}</strong>
                                    <div class="mt-1">${new Date(request.reviewed_at).toLocaleString()}</div>
                                </div>
                                ` : ''}
                                ${request.reject_reason ? `
                                <div>
                                    <strong><i class="bi bi-exclamation-triangle me-2 text-danger"></i>{{ __('Rejection Reason:') }}</strong>
                                    <div class="alert alert-danger mt-2 mb-0">${request.reject_reason}</div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-left-right me-2 text-primary"></i>
                            {{ __('Changes Comparison') }}
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%; padding: 1rem;">
                                            <i class="bi bi-list-ul me-2"></i>{{ __('Field') }}
                                        </th>
                                        <th style="width: 37.5%; padding: 1rem;" class="text-danger">
                                            <i class="bi bi-x-circle me-2"></i>{{ __('Original Value') }}
                                        </th>
                                        <th style="width: 37.5%; padding: 1rem;" class="text-success">
                                            <i class="bi bi-check-circle me-2"></i>{{ __('Edited Value') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
            `;

            if (changes.length > 0) {
                changes.forEach(change => {
                    const origDisplay = formatValue(change.original);
                    const editDisplay = formatValue(change.edited);
                    html += `
                        <tr>
                            <td style="padding: 1rem; vertical-align: middle;">
                                <strong class="text-dark">${formatFieldName(change.field)}</strong>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;" class="bg-light">
                                <div class="text-danger">${origDisplay}</div>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;" class="bg-light">
                                <div class="text-success">${editDisplay}</div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                            <span class="text-muted">{{ __('No changes detected') }}</span>
                        </td>
                    </tr>
                `;
            }

            html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('editRequestDetails').innerHTML = html;

            // Set up action buttons
            let actionsHtml = '';
            if (request.status == 'pending') {
                actionsHtml = `
                    <button type="button" class="btn btn-success px-4" onclick="approveEditRequest(${request.id})">
                        <i class="bi bi-check-circle me-2"></i>{{ __('Approve') }}
                    </button>
                    <button type="button" class="btn btn-danger px-4" onclick="showRejectModal(${request.id})">
                        <i class="bi bi-x-circle me-2"></i>{{ __('Reject') }}
                    </button>
                `;
            }
            actionsHtml += '<button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal"><i class="bi bi-x-lg me-2"></i>{{ __('Close') }}</button>';
            document.getElementById('editRequestActions').innerHTML = actionsHtml;
        }

        function formatValue(value) {
            if (value === null || value === undefined || value === '') {
                return '<span class="badge bg-secondary">(empty)</span>';
            }
            if (typeof value === 'object') {
                return '<pre class="bg-light p-2 rounded" style="max-height: 150px; overflow-y: auto; font-size: 0.85rem;">' + JSON.stringify(value, null, 2) + '</pre>';
            }
            if (typeof value === 'boolean') {
                return value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>';
            }
            const strValue = String(value);
            if (strValue.length > 150) {
                return '<div class="text-truncate" style="max-width: 300px;" title="' + strValue.replace(/"/g, '&quot;') + '">' + strValue.substring(0, 150) + '...</div>';
            }
            return '<div>' + strValue + '</div>';
        }

        function formatFieldName(field) {
            return field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function approveEditRequest(requestId) {
            if (!confirm('{{ __('Are you sure you want to approve this edit request? The changes will be applied to the property.') }}')) {
                return;
            }

            const formData = {
                edit_request_id: requestId,
                status: 'approved'
            };

            fetch('{{ route("property-edit-requests.update-status") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error === false) {
                    alert('{{ __('Edit request approved successfully!') }}');
                    location.reload();
                } else {
                    alert('{{ __('Error:') }} ' + (data.message || '{{ __('Something went wrong.') }}'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('Error approving edit request.') }}');
            });
        }

        function showRejectModal(requestId) {
            document.getElementById('rejectRequestId').value = requestId;
            document.getElementById('reject_reason').value = '';
            const modal = new bootstrap.Modal(document.getElementById('rejectReasonModal'));
            modal.show();
        }

        document.getElementById('rejectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                edit_request_id: document.getElementById('rejectRequestId').value,
                status: 'rejected',
                reject_reason: document.getElementById('reject_reason').value
            };

            fetch('{{ route("property-edit-requests.update-status") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error === false) {
                    alert('{{ __('Edit request rejected successfully!') }}');
                    bootstrap.Modal.getInstance(document.getElementById('rejectReasonModal')).hide();
                    location.reload();
                } else {
                    alert('{{ __('Error:') }} ' + (data.message || '{{ __('Something went wrong.') }}'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('Error rejecting edit request.') }}');
            });
        });
    </script>
@endsection

