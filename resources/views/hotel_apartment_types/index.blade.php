@extends('layouts.main')

@section('title')
    {{ __('Hotel Apartment Types') }}
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

    @if(has_permissions('create', 'hotel_apartment_types'))
        <section class="section">
            <div class="card">
                <div class="card-header">
                    <div class="divider">
                        <div class="divider-text">
                            <h4>{{ __('Create Hotel Apartment Type') }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card-content">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                {!! Form::open(['url' => route('hotel-apartment-types.store'), 'data-parsley-validate', 'class' => 'create-form']) !!}
                                @csrf

                                <div class="row">
                                    {{-- Name --}}
                                    <div class="col-sm-12 col-md-6 form-group mandatory">
                                        {{ Form::label('name', __('Name'), ['class' => 'form-label text-center']) }}
                                        {{ Form::text('name', '', ['class' => 'form-control', 'placeholder' => __('Apartment Type Name'), 'data-parsley-required' => 'true']) }}
                                    </div>

                                    {{-- Description --}}
                                    <div class="col-sm-12 col-md-6 form-group">
                                        {{ Form::label('description', __('Description'), ['class' => 'form-label text-center']) }}
                                        {{ Form::textarea('description', '', ['class' => 'form-control', 'placeholder' => __('Apartment Type Description'), 'rows' => 3]) }}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-12 d-flex justify-content-end">
                                        {{ Form::submit(__('Save'), ['class' => 'btn btn-primary me-1 mb-1']) }}
                                    </div>
                                </div>

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if(has_permissions('read', 'hotel_apartment_types'))
        <section class="section">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Hotel Apartment Types List') }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <table class="table table-striped" id="apartment_types_table" data-toggle="table" data-url="{{ route('hotel-apartment-types.list') }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                        <th data-field="name" data-sortable="true">{{ __('Name') }}</th>
                                        <th data-field="description" data-sortable="true">{{ __('Description') }}</th>
                                        <th data-field="created_at" data-sortable="true" data-visible="false">{{ __('Created At') }}</th>
                                        <th data-field="updated_at" data-sortable="true" data-visible="false">{{ __('Updated At') }}</th>
                                        <th data-field="operate" data-sortable="false" data-events="actionEvents">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">{{ __('Edit Apartment Type') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">{{ __('Description') }}</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        function queryParams(p) {
            return {
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                limit: p.limit,
                search: p.search
            };
        }

        window.actionEvents = {
            'click .edit-apartment-type': function(e, value, row, index) {
                $('#edit_id').val(row.id);
                $('#edit_name').val(row.name);
                $('#edit_description').val(row.description);
                $('#editForm').attr('action', '{{ route("hotel-apartment-types.update", ":id") }}'.replace(':id', row.id));
                $('#editModal').modal('show');
            },
            'click .delete-apartment-type': function(e, value, row, index) {
                if (confirm('Are you sure you want to delete this apartment type?')) {
                    $.ajax({
                        url: '{{ route("hotel-apartment-types.destroy", ":id") }}'.replace(':id', row.id),
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (!response.error) {
                                showSuccessToast(response.message);
                                $('#apartment_types_table').bootstrapTable('refresh');
                            } else {
                                showErrorToast(response.message);
                            }
                        }
                    });
                }
            }
        };

        $(document).ready(function() {
            // Initialize form validation
            $('.create-form').on('submit', function(e) {
                e.preventDefault();

                var form = $(this);

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (!response.error) {
                            form[0].reset();
                            showSuccessToast(response.message);
                            $('#apartment_types_table').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function(xhr) {
                        showErrorToast(xhr.responseJSON.message);
                    }
                });
            });

            // Initialize edit form submission
            $('#editForm').on('submit', function(e) {
                e.preventDefault();

                var form = $(this);

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (!response.error) {
                            $('#editModal').modal('hide');
                            showSuccessToast(response.message);
                            $('#apartment_types_table').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.message);
                        }
                    },
                    error: function(xhr) {
                        showErrorToast(xhr.responseJSON.message);
                    }
                });
            });
        });
    </script>
@endsection
