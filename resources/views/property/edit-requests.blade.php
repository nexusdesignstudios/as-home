@extends('layouts.main')

@section('title')
    {{ __('Property Edit Requests') }}
@endsection

@section('css')
<style>
    /* Page-specific styles - minimal, following the same pattern as other pages */
    
    /* Button group spacing */
    .d-flex.gap-2 {
        gap: 0.5rem !important;
    }
    
    /* Ensure content doesn't overflow on any screen size */
    #main-content {
        width: 100%;
        box-sizing: border-box;
    }
    
    .section {
        width: 100%;
        box-sizing: border-box;
    }
    
    .card {
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Table responsive for mobile */
    @media (max-width: 768px) {
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .d-flex.gap-2 {
            flex-direction: column;
            width: 100%;
        }
        
        .d-flex.gap-2 .btn {
            width: 100%;
        }
    }
</style>
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


@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-12">
                        <h5>{{ __('Property Edit Requests') }}</h5>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2" role="group">
                            <a href="{{ route('property-edit-requests.index', ['status' => 'pending']) }}" 
                               class="btn btn-sm {{ $status == 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">
                                {{ __('Pending') }} 
                                <span class="badge bg-light text-dark ms-1">
                                    @php
                                        try {
                                            $pendingCount = \App\Models\PropertyEditRequest::where('status', 'pending')->count();
                                        } catch (\Exception $e) {
                                            $pendingCount = 0;
                                        }
                                    @endphp
                                    {{ $pendingCount }}
                                </span>
                            </a>
                            <a href="{{ route('property-edit-requests.index', ['status' => 'approved']) }}" 
                               class="btn btn-sm {{ $status == 'approved' ? 'btn-success' : 'btn-outline-success' }}">
                                {{ __('Approved') }}
                                <span class="badge bg-light text-dark ms-1">
                                    @php
                                        try {
                                            $approvedCount = \App\Models\PropertyEditRequest::where('status', 'approved')->count();
                                        } catch (\Exception $e) {
                                            $approvedCount = 0;
                                        }
                                    @endphp
                                    {{ $approvedCount }}
                                </span>
                            </a>
                            <a href="{{ route('property-edit-requests.index', ['status' => 'rejected']) }}" 
                               class="btn btn-sm {{ $status == 'rejected' ? 'btn-danger' : 'btn-outline-danger' }}">
                                {{ __('Rejected') }}
                                <span class="badge bg-light text-dark ms-1">
                                    @php
                                        try {
                                            $rejectedCount = \App\Models\PropertyEditRequest::where('status', 'rejected')->count();
                                        } catch (\Exception $e) {
                                            $rejectedCount = 0;
                                        }
                                    @endphp
                                    {{ $rejectedCount }}
                                </span>
                            </a>
                            <a href="{{ route('property-edit-requests.index', ['status' => 'all']) }}" 
                               class="btn btn-sm {{ $status == 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                                {{ __('All') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(isset($error))
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> {{ $error }}
                        @if(strpos($error, 'table does not exist') !== false)
                            <br><br>
                            <strong>To fix this:</strong>
                            <ol>
                                <li>Run the migration: <code>php artisan migrate</code></li>
                                <li>Or manually create the table using the migration file: <code>database/migrations/2025_01_25_000000_create_property_edit_requests_table.php</code></li>
                            </ol>
                        @endif
                    </div>
                @endif
                @if($editRequests->count() > 0)
                    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                        <table class="table table-striped table-hover" style="min-width: 800px; width: 100%;">
                            <thead>
                                <tr>
                                    <th style="min-width: 60px;">{{ __('ID') }}</th>
                                    <th style="min-width: 200px;">{{ __('Property') }}</th>
                                    <th style="min-width: 150px;">{{ __('Owner') }}</th>
                                    <th style="min-width: 140px;">{{ __('Requested At') }}</th>
                                    <th style="min-width: 100px;">{{ __('Status') }}</th>
                                    <th style="min-width: 120px;">{{ __('Changes') }}</th>
                                    <th style="min-width: 100px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($editRequests as $request)
                                    <tr>
                                        <td>#{{ $request->id }}</td>
                                        <td>
                                            <a href="{{ route('property.edit', $request->property_id) }}" target="_blank">
                                                {{ $request->property->title ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $request->requestedBy->name ?? 'N/A' }}<br>
                                            <small class="text-muted">{{ $request->requestedBy->email ?? '' }}</small>
                                        </td>
                                        <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if($request->status == 'pending')
                                                <span class="badge bg-warning">{{ __('Pending') }}</span>
                                            @elseif($request->status == 'approved')
                                                <span class="badge bg-success">{{ __('Approved') }}</span>
                                                @if($request->reviewedBy)
                                                    <br><small class="text-muted">By: {{ $request->reviewedBy->name }}</small>
                                                @endif
                                            @else
                                                <span class="badge bg-danger">{{ __('Rejected') }}</span>
                                                @if($request->reviewedBy)
                                                    <br><small class="text-muted">By: {{ $request->reviewedBy->name }}</small>
                                                @endif
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
                                            <span class="badge bg-info">{{ count($changes) }} {{ __('fields changed') }}</span>
                                        </td>
                                        <td>
                                            @if($request->status == 'pending')
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        onclick="viewEditRequest({{ $request->id }})">
                                                    <i class="bi bi-eye"></i> {{ __('View') }}
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        onclick="viewEditRequest({{ $request->id }})">
                                                    <i class="bi bi-eye"></i> {{ __('View') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> {{ __('No edit requests found.') }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- View Edit Request Modal -->
    <div class="modal fade" id="viewEditRequestModal" tabindex="-1" aria-labelledby="viewEditRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewEditRequestModalLabel">{{ __('Property Edit Request Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editRequestDetails">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="editRequestActions">
                    <!-- Actions will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Reason Modal -->
    <div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectReasonModalLabel">{{ __('Reject Edit Request') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectForm">
                    <div class="modal-body">
                        <input type="hidden" id="rejectRequestId" name="edit_request_id">
                        <input type="hidden" name="status" value="rejected">
                        <div class="mb-3">
                            <label for="reject_reason" class="form-label">{{ __('Rejection Reason') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reject_reason" name="reject_reason" rows="4" required 
                                      placeholder="{{ __('Please provide a reason for rejecting this edit request...') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Reject Request') }}</button>
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
                    console.log('Edit request data:', data);
                    if (data.error === false) {
                        console.log('Edit request object:', data.data.edit_request);
                        console.log('Original data:', data.data.edit_request.original_data);
                        console.log('Edited data:', data.data.edit_request.edited_data);
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
            console.log('displayEditRequest called with:', request);
            
            // Ensure original_data and edited_data are objects, not strings
            let original = request.original_data || {};
            let edited = request.edited_data || {};
            
            // If they're strings (JSON), parse them
            if (typeof original === 'string') {
                try {
                    original = JSON.parse(original);
                } catch (e) {
                    console.error('Error parsing original_data:', e);
                    original = {};
                }
            }
            if (typeof edited === 'string') {
                try {
                    edited = JSON.parse(edited);
                } catch (e) {
                    console.error('Error parsing edited_data:', e);
                    edited = {};
                }
            }
            
            console.log('Parsed original:', original);
            console.log('Parsed edited:', edited);
            
            const changes = [];
            
            // Handle vacation apartments separately (special comparison)
            if (edited.vacation_apartments || original.vacation_apartments) {
                const origApartments = original.vacation_apartments || [];
                const editApartments = edited.vacation_apartments || [];
                
                // Compare vacation apartments
                const apartmentChanges = compareVacationApartments(origApartments, editApartments);
                if (apartmentChanges.length > 0) {
                    changes.push({
                        field: 'vacation_apartments',
                        original: origApartments,
                        edited: editApartments,
                        apartment_changes: apartmentChanges,
                        is_vacation_apartments: true
                    });
                }
            }
            
            // Find all changes - compare all fields
            const allKeys = new Set([...Object.keys(original), ...Object.keys(edited)]);
            const ignoredKeys = ['id', 'created_at', 'updated_at', 'deleted_at', 'vacation_apartments'];
            
            allKeys.forEach(key => {
                if (!ignoredKeys.includes(key)) {
                    const origValue = original[key];
                    const editValue = edited[key];
                    
                    // Deep comparison for objects and arrays
                    let valuesAreDifferent = false;
                    
                    if (origValue === null || origValue === undefined) {
                        valuesAreDifferent = (editValue !== null && editValue !== undefined);
                    } else if (editValue === null || editValue === undefined) {
                        valuesAreDifferent = true;
                    } else if (typeof origValue === 'object' || typeof editValue === 'object') {
                        // For objects/arrays, use JSON stringify for comparison
                        try {
                            valuesAreDifferent = JSON.stringify(origValue) !== JSON.stringify(editValue);
                        } catch (e) {
                            // Fallback to string comparison
                            valuesAreDifferent = String(origValue) !== String(editValue);
                        }
                    } else {
                        // For primitives, use simple comparison
                        valuesAreDifferent = origValue !== editValue;
                    }
                    
                    if (valuesAreDifferent) {
                        changes.push({
                            field: key,
                            original: origValue,
                            edited: editValue
                        });
                    }
                }
            });
            
            console.log('Total changes found:', changes.length);
            console.log('Changes:', changes);

            // Sort changes by field name for better readability
            changes.sort((a, b) => a.field.localeCompare(b.field));

            let html = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Request Information') }}</h6>
                                <p class="mb-1"><strong>{{ __('Property:') }}</strong> ${request.property ? request.property.title : 'N/A'}</p>
                                <p class="mb-1"><strong>{{ __('Owner:') }}</strong> ${request.requested_by ? request.requested_by.name : 'N/A'}</p>
                                <p class="mb-1"><strong>{{ __('Email:') }}</strong> ${request.requested_by ? request.requested_by.email : 'N/A'}</p>
                                <p class="mb-1"><strong>{{ __('Requested:') }}</strong> ${new Date(request.created_at).toLocaleString()}</p>
                                <p class="mb-0"><strong>{{ __('Status:') }}</strong> 
                                    <span class="badge bg-${request.status == 'pending' ? 'warning' : (request.status == 'approved' ? 'success' : 'danger')}">
                                        ${request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Review Information') }}</h6>
                                <p class="mb-1"><strong>{{ __('Total Changes:') }}</strong> 
                                    <span class="badge bg-info">${changes.length} {{ __('fields') }}</span>
                                </p>
                                ${request.reviewed_by ? `<p class="mb-1"><strong>{{ __('Reviewed By:') }}</strong> ${request.reviewed_by.name}</p>` : '<p class="mb-1 text-muted">{{ __('Not reviewed yet') }}</p>'}
                                ${request.reviewed_at ? `<p class="mb-1"><strong>{{ __('Reviewed At:') }}</strong> ${new Date(request.reviewed_at).toLocaleString()}</p>` : ''}
                                ${request.reject_reason ? `<div class="alert alert-danger mt-2 mb-0"><strong>{{ __('Rejection Reason:') }}</strong><br>${request.reject_reason}</div>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>{{ __('Summary:') }}</strong> ${changes.length} {{ __('field(s) have been modified. Review the changes below before approving or rejecting.') }}
                </div>
                
                <h5 class="mb-3">{{ __('Detailed Changes Comparison') }}</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">{{ __('Field Name') }}</th>
                                <th style="width: 37.5%;" class="text-danger">
                                    <i class="bi bi-x-circle me-1"></i>{{ __('Original Value') }}
                                </th>
                                <th style="width: 37.5%;" class="text-success">
                                    <i class="bi bi-check-circle me-1"></i>{{ __('New Value (Requested)') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            if (changes.length > 0) {
                changes.forEach(change => {
                    // Special handling for vacation apartments
                    if (change.is_vacation_apartments && change.apartment_changes) {
                        html += `
                            <tr>
                                <td colspan="3" class="bg-info text-white">
                                    <strong><i class="bi bi-building"></i> Vacation Apartments Changes</strong>
                                </td>
                            </tr>
                        `;
                        
                        change.apartment_changes.forEach(aptChange => {
                            html += `
                                <tr>
                                    <td>
                                        <strong>Apartment: ${aptChange.apartment_number || 'N/A'}</strong>
                                        <br><small class="text-muted">ID: ${aptChange.apartment_id}</small>
                                    </td>
                                    <td class="bg-light">
                                        <div class="text-danger fw-bold">
                                            ${formatVacationApartmentValue(aptChange.original)}
                                        </div>
                                    </td>
                                    <td class="bg-light">
                                        <div class="text-success fw-bold">
                                            ${formatVacationApartmentValue(aptChange.edited)}
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    } else if (change.field === 'hotel_rooms') {
                        // Special handling for hotel rooms
                        html += `
                            <tr>
                                <td>
                                    <strong>${formatFieldName(change.field)}</strong>
                                    <br><small class="text-muted">${change.field}</small>
                                </td>
                                <td class="bg-light">
                                    <div class="text-danger fw-bold">${formatHotelRoomValue(change.original || [])}</div>
                                </td>
                                <td class="bg-light">
                                    <div class="text-success fw-bold">${formatHotelRoomValue(change.edited || [])}</div>
                                </td>
                            </tr>
                        `;
                    } else {
                        const origDisplay = formatValue(change.original);
                        const editDisplay = formatValue(change.edited);
                        html += `
                            <tr>
                                <td>
                                    <strong>${formatFieldName(change.field)}</strong>
                                    <br><small class="text-muted">${change.field}</small>
                                </td>
                                <td class="bg-light">
                                    <div class="text-danger fw-bold">${origDisplay}</div>
                                </td>
                                <td class="bg-light">
                                    <div class="text-success fw-bold">${editDisplay}</div>
                                </td>
                            </tr>
                        `;
                    }
                });
            } else {
                html += `
                    <tr>
                        <td colspan="3" class="text-center py-4">
                            <i class="bi bi-info-circle text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">{{ __('No changes detected in this request.') }}</p>
                        </td>
                    </tr>
                `;
            }

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            document.getElementById('editRequestDetails').innerHTML = html;

            // Set up action buttons
            let actionsHtml = '';
            if (request.status == 'pending') {
                actionsHtml = `
                    <button type="button" class="btn btn-success" onclick="approveEditRequest(${request.id})">
                        <i class="bi bi-check-circle"></i> {{ __('Approve') }}
                    </button>
                    <button type="button" class="btn btn-danger" onclick="showRejectModal(${request.id})">
                        <i class="bi bi-x-circle"></i> {{ __('Reject') }}
                    </button>
                `;
            }
            actionsHtml += '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>';
            document.getElementById('editRequestActions').innerHTML = actionsHtml;
        }

        function formatValue(value) {
            if (value === null || value === undefined || value === '') {
                return '<span class="badge bg-secondary">(empty)</span>';
            }
            if (typeof value === 'object' && value !== null) {
                // Handle arrays
                if (Array.isArray(value)) {
                    if (value.length === 0) {
                        return '<span class="badge bg-secondary">(empty array)</span>';
                    }
                    return '<pre class="bg-white p-2 border rounded" style="max-height: 150px; overflow-y: auto; font-size: 0.85rem;">' + JSON.stringify(value, null, 2) + '</pre>';
                }
                // Handle objects
                return '<pre class="bg-white p-2 border rounded" style="max-height: 150px; overflow-y: auto; font-size: 0.85rem;">' + JSON.stringify(value, null, 2) + '</pre>';
            }
            if (typeof value === 'boolean') {
                return value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>';
            }
            if (typeof value === 'number') {
                return '<code>' + value + '</code>';
            }
            const strValue = String(value);
            // Truncate very long strings
            if (strValue.length > 300) {
                return '<div class="text-break">' + strValue.substring(0, 300) + '...</div><small class="text-muted">(truncated, ' + strValue.length + ' characters total)</small>';
            }
            return '<div class="text-break">' + strValue + '</div>';
        }

        function formatFieldName(field) {
            return field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function formatHotelRoomValue(rooms) {
            if (!Array.isArray(rooms) || rooms.length === 0) {
                return '<span class="badge bg-secondary">(no rooms)</span>';
            }
            
            let html = '<div class="small">';
            rooms.forEach((room, index) => {
                html += `<div class="mb-2 p-2 border rounded">
                    <strong>Room ID: ${room.id || 'N/A'}</strong><br>
                    <div class="mt-1">
                        <strong>Description:</strong> ${room.description || '<span class="text-muted">(empty)</span>'}
                    </div>
                </div>`;
            });
            html += '</div>';
            return html;
        }

        function compareVacationApartments(original, edited) {
            const changes = [];
            const origMap = new Map(original.map(apt => [apt.id, apt]));
            const editMap = new Map(edited.map(apt => [apt.id, apt]));
            
            // Check for updated apartments
            editMap.forEach((editApt, id) => {
                const origApt = origMap.get(id);
                if (!origApt) {
                    // New apartment
                    changes.push({
                        apartment_id: id,
                        apartment_number: editApt.apartment_number,
                        type: 'new',
                        original: null,
                        edited: editApt
                    });
                } else {
                    // Check for changes
                    const fieldChanges = {};
                    ['quantity', 'price_per_night', 'discount_percentage', 'max_guests', 'bedrooms', 'bathrooms', 'status', 'availability_type', 'description'].forEach(field => {
                        if (origApt[field] !== editApt[field]) {
                            fieldChanges[field] = {
                                original: origApt[field],
                                edited: editApt[field]
                            };
                        }
                    });
                    
                    if (Object.keys(fieldChanges).length > 0) {
                        changes.push({
                            apartment_id: id,
                            apartment_number: editApt.apartment_number,
                            type: 'updated',
                            original: origApt,
                            edited: editApt,
                            field_changes: fieldChanges
                        });
                    }
                }
            });
            
            // Check for deleted apartments
            origMap.forEach((origApt, id) => {
                if (!editMap.has(id)) {
                    changes.push({
                        apartment_id: id,
                        apartment_number: origApt.apartment_number,
                        type: 'deleted',
                        original: origApt,
                        edited: null
                    });
                }
            });
            
            return changes;
        }

        function formatVacationApartmentValue(apartment) {
            if (!apartment) {
                return '<span class="badge bg-secondary">(deleted)</span>';
            }
            
            let html = '<div class="small">';
            if (apartment.quantity !== undefined) {
                html += `<strong>Quantity:</strong> <code>${apartment.quantity}</code><br>`;
            }
            if (apartment.price_per_night !== undefined) {
                html += `<strong>Price/Night:</strong> <code>${apartment.price_per_night}</code><br>`;
            }
            if (apartment.max_guests !== undefined) {
                html += `<strong>Max Guests:</strong> <code>${apartment.max_guests}</code><br>`;
            }
            if (apartment.bedrooms !== undefined) {
                html += `<strong>Bedrooms:</strong> <code>${apartment.bedrooms}</code><br>`;
            }
            if (apartment.bathrooms !== undefined) {
                html += `<strong>Bathrooms:</strong> <code>${apartment.bathrooms}</code>`;
            }
            html += '</div>';
            return html;
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

