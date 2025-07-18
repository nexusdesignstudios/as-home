@extends('layouts.main')

@section('title')
    {{ __('Hotel Room Types') }}
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

    @if(has_permissions('create', 'hotel_room_types'))
        <section class="section">
            <div class="card">
                <div class="card-header">
                    <div class="divider">
                        <div class="divider-text">
                            <h4>{{ __('Create Hotel Room Type') }}</h4>
                        </div>
                    </div>
                </div>

                <div class="card-content">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                {!! Form::open(['url' => route('hotel_room_types.store'), 'data-parsley-validate', 'class' => 'create-form']) !!}
                                @csrf

                                <div class="row">
                                    {{-- Name --}}
                                    <div class="col-sm-12 col-md-6 form-group mandatory">
                                        {{ Form::label('name', __('Name'), ['class' => 'form-label text-center']) }}
                                        {{ Form::text('name', '', ['class' => 'form-control', 'placeholder' => __('Room Type Name'), 'data-parsley-required' => 'true']) }}
                                    </div>

                                    {{-- Description --}}
                                    <div class="col-sm-12 col-md-6 form-group">
                                        {{ Form::label('description', __('Description'), ['class' => 'form-label text-center']) }}
                                        {{ Form::textarea('description', '', ['class' => 'form-control', 'placeholder' => __('Room Type Description'), 'rows' => 3]) }}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-12 col-md-6 form-group">
                                        {{ Form::label('status', __('Status'), ['class' => 'form-label text-center']) }}
                                        <div class="form-check form-switch">
                                            {{ Form::checkbox('status', '1', true, ['class' => 'form-check-input', 'id' => 'status']) }}
                                            <label class="form-check-label" for="status">{{ __('Active') }}</label>
                                        </div>
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

    @if(has_permissions('read', 'hotel_room_types'))
        <section class="section">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Hotel Room Types List') }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <table class="table table-striped" id="room_types_table" data-toggle="table" data-url="{{ route('hotel_room_types.show') }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="" data-show-export="true" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                        <th data-field="name" data-sortable="true">{{ __('Name') }}</th>
                                        <th data-field="description" data-sortable="true">{{ __('Description') }}</th>
                                        <th data-field="status" data-sortable="true" data-formatter="statusFormatter">{{ __('Status') }}</th>
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
                    <h5 class="modal-title" id="editModalLabel">{{ __('Edit Room Type') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('hotel_room_types.update') }}" method="POST" id="editForm">
                    @csrf
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

        function statusFormatter(value, row) {
            if (value == 1) {
                return '<span class="badge bg-success">Active</span>';
            } else {
                return '<span class="badge bg-danger">Inactive</span>';
            }
        }

        window.actionEvents = {
            'click .edit-room-type': function(e, value, row, index) {
                $('#edit_id').val(row.id);
                $('#edit_name').val(row.name);
                $('#edit_description').val(row.description);
                $('#editModal').modal('show');
            },
            'click .change-status': function(e, value, row, index) {
                let status = $(e.target).data('status');
                let id = row.id;

                $.ajax({
                    url: "{{ route('hotel_room_types.status') }}",
                    type: "POST",
                    data: {
                        id: id,
                        status: status,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (!response.error) {
                            showSuccessToast(response.message);
                            $('#room_types_table').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.message);
                        }
                    }
                });
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
                            $('#room_types_table').bootstrapTable('refresh');
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
                            $('#room_types_table').bootstrapTable('refresh');
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

        function editRoomType(url, isAjax, id) {
            $('#edit_id').val(id);

            // Fetch room type data
            $.ajax({
                url: "{{ route('hotel_room_types.show') }}",
                type: "GET",
                data: {
                    id: id
                },
                success: function(response) {
                    if (response && response.rows && response.rows.length > 0) {
                        let roomType = response.rows.find(item => item.id == id);
                        if (roomType) {
                            $('#edit_name').val(roomType.name);
                            $('#edit_description').val(roomType.description);
                            $('#editModal').modal('show');
                        }
                    }
                }
            });
        }
    </script>
@endsection
