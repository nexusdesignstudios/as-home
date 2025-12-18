@extends('layouts.main')

@section('title', 'Property Documents Check')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Property Documents Database Check</h3>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Properties</h5>
                        <h2 class="text-primary">{{ $totalProperties }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">With Documents</h5>
                        <h2 class="text-success">{{ $propertiesWithDocuments }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">All 4 Documents</h5>
                        <h2 class="text-info">{{ $propertiesWithAllDocuments }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Without Documents</h5>
                        <h2 class="text-danger">{{ $totalProperties - $propertiesWithDocuments }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Type Statistics -->
        <div class="card">
            <div class="card-header">
                <h4>Document Type Statistics</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-file-earmark-text fs-1 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Identity Proof</h6>
                                <h4>{{ $documentStats['identity_proof'] }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-person-badge fs-1 text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>National ID/Passport</h6>
                                <h4>{{ $documentStats['national_id_passport'] }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-receipt fs-1 text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Utilities Bills</h6>
                                <h4>{{ $documentStats['utilities_bills'] }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="bi bi-file-earmark-check fs-1 text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6>Power of Attorney</h6>
                                <h4>{{ $documentStats['power_of_attorney'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Properties with Documents ({{ count($propertiesList) }})</h4>
                <button class="btn btn-sm btn-primary" onclick="exportToCSV()">
                    <i class="bi bi-download"></i> Export to CSV
                </button>
            </div>
            <div class="card-body">
                @if(count($propertiesList) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="propertiesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Owner ID</th>
                                    <th>Classification</th>
                                    <th>Status</th>
                                    <th>Identity Proof</th>
                                    <th>National ID</th>
                                    <th>Utilities Bills</th>
                                    <th>Power of Attorney</th>
                                    <th>Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($propertiesList as $prop)
                                    <tr>
                                        <td>{{ $prop['id'] }}</td>
                                        <td>{{ $prop['title'] }}</td>
                                        <td>{{ $prop['owner_id'] }}</td>
                                        <td>{{ $prop['classification'] }}</td>
                                        <td>
                                            <span class="badge bg-{{ $prop['status'] == 1 ? 'success' : 'danger' }}">
                                                {{ $prop['status'] == 1 ? 'Active' : 'Inactive' }}
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $prop['request_status'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($prop['documents']['identity_proof'])
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @else
                                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($prop['documents']['national_id_passport'])
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @else
                                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($prop['documents']['utilities_bills'])
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @else
                                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($prop['documents']['power_of_attorney'])
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @else
                                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $prop['count'] == 4 ? 'success' : ($prop['count'] >= 2 ? 'warning' : 'info') }}">
                                                {{ $prop['count'] }}/4
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('property.edit', $prop['id']) }}" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No properties found with documents.
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('propertiesTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    // Headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Data rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach((td, index) => {
            if (index === 4) { // Status column
                row.push(td.textContent.trim().replace(/\s+/g, ' '));
            } else if (index === 5 || index === 6 || index === 7 || index === 8) { // Document columns
                row.push(td.querySelector('.bi-check-circle-fill') ? 'Yes' : 'No');
            } else {
                row.push(td.textContent.trim().replace(/,/g, ';'));
            }
        });
        csv.push(row.join(','));
    });
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'property_documents_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endsection

