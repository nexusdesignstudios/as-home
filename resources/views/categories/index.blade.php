@extends('layouts.main')

@section('title')
    {{ __('Categories') }}
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

        {{-- Add Category Button --}}
        @if(has_permissions('create', 'categories'))
            <div class="col-md-12 text-end">
                <button class="btn mb-3 btn-primary add-category-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                        <path
                            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z">
                        </path>
                    </svg>
                    {{ __('Add Category') }}
                </button>
            </div>
        @endif

        {{-- Create Category Section --}}
        <div class="card add-category mt-3">
            <div class="card-header">
                <div class="divider">
                    <div class="divider-text">
                        <h4>{{ __('Create Category') }}</h4>
                    </div>
                </div>
            </div>
            <div class="card-content">
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        {{ __('You can now create multiple categories with different property classifications at once. Simply select multiple classifications and the system will create a separate category for each one.') }}
                    </div>
                    <div class="row">
                        {!! Form::open(['url' => route('categories.store'), 'data-parsley-validate', 'files' => true]) !!}
                        <div class=" row">

                            {{-- Category --}}
                            <div class="col-md-6 col-sm-12 form-group mandatory">
                                {{ Form::label('category', __('Category'), ['class' => 'form-label text-center']) }}
                                {{ Form::text('category', '', [ 'class' => 'form-control', 'placeholder' => trans('Category'), 'data-parsley-required' => 'true', 'id' => 'category']) }}
                            </div>

                            {{-- Slug --}}
                            <div class="col-md-6 col-12 form-group">
                                {{ Form::label('slug', __('Slug'), ['class' => 'form-label col-12 ']) }}
                                {{ Form::text('slug', '', [ 'class' => 'form-control ', 'placeholder' =>  __('Slug'), 'id' => 'slug', ]) }}
                                <small class="text-danger text-sm">{{ __("Only Small English Characters, Numbers And Hypens Allowed") }}</small>
                            </div>

                            {{-- Facilities --}}
                            <div class="col-md-6 col-sm-12 form-group mandatory">
                                {{ Form::label('type', __('Facilities'), ['class' => 'form-label text-center']) }}
                                <select data-placeholder="{{ __('Choose Facilities') }}" name="parameter_type[]" class="form-control form-select chosen-select" id="select_parameter_type" multiple data-parsley-required="true" data-parsley-minSelect='1'>
                                    @foreach ($parameters as $parameter)
                                        <option value={{ $parameter->id }}>{{ $parameter->name }} {{ $parameter->is_required == 1 ? "*" : ""}}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">{{ __('Select facilities and they will appear below for ordering') }}</small>
                            </div>

                            {{-- Facilities Order Preview --}}
                            <div class="col-md-12 col-sm-12 form-group">
                                {{ Form::label('facilities_order', __('Facilities Order & Preview'), ['class' => 'form-label']) }}
                                <small class="text-muted d-block mb-2">{{ __('Drag and drop to reorder facilities. This order will be saved when you create the category.') }}</small>
                                
                                <div id="create_par" class="d-flex flex-wrap gap-2 p-3 border rounded" style="min-height: 60px; background-color: #f8f9fa;">
                                    <p class="text-muted m-0 w-100" id="no-facilities-message">{{ __('No facilities selected. Please select facilities from the dropdown above.') }}</p>
                                </div>
                                
                                <input type="hidden" name="create_seq" id="create_seq">
                                
                                <div class="alert alert-info mt-2" id="facilities-preview-info" style="display: none;">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>{{ __('Frontend Preview:') }}</strong> 
                                    <div id="facilities-preview-text" class="mt-2"></div>
                                </div>
                                
                                <div class="alert alert-success mt-2" id="facilities-order-info" style="display: none;">
                                    <i class="bi bi-check-circle"></i>
                                    <strong>{{ __('Order Saved:') }}</strong> 
                                    <div id="facilities-order-text" class="mt-2"></div>
                                </div>
                            </div>

                            {{-- Image --}}
                            <div class="col-md-6 col-sm-12 form-group mandatory">
                                {{ Form::label('image', __('Image'), ['class' => 'form-label text-center']) }}
                                {{ Form::file('image', ['class' => 'form-control', 'data-parsley-required' => 'true', 'accept' => '.svg']) }}
                            </div>

                            {{-- Property Classification --}}
                            <div class="col-md-6 col-sm-12 form-group mandatory">
                                {{ Form::label('property_classifications', __('Property Classifications'), ['class' => 'form-label text-center']) }}
                                <div class="form-group">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="property_classifications[]" value="1" id="classification1">
                                        <label class="form-check-label" for="classification1">{{ __('Sell/Long Term Rent') }}</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="property_classifications[]" value="2" id="classification2">
                                        <label class="form-check-label" for="classification2">{{ __('Commercial') }}</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="property_classifications[]" value="3" id="classification3">
                                        <label class="form-check-label" for="classification3">{{ __('New Project') }}</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="property_classifications[]" value="4" id="classification4">
                                        <label class="form-check-label" for="classification4">{{ __('Vacation Homes') }}</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="property_classifications[]" value="5" id="classification5">
                                        <label class="form-check-label" for="classification5">{{ __('Hotel Booking') }}</label>
                                    </div>
                                </div>
                                <small class="text-muted">{{ __('Select one or more classifications to create multiple categories') }}</small>
                            </div>

                        </div>
                        <div class="row">

                            {{-- Meta Title --}}
                            <div class="col-md-4 col-sm-12 form-group">
                                {{ Form::label('title', __('Meta Title'), ['class' => 'form-label text-center']) }}
                                <input type="text" name="meta_title" class="form-control" id="meta_title" oninput="getWordCount('meta_title','meta_title_count','19.9px arial')" placeholder="{{ __('Meta Title') }}">
                                <h6 id="meta_title_count">0</h6>
                            </div>

                            {{-- Meta Keywords --}}
                            <div class="col-md-4 col-sm-12 form-group">
                                {{ Form::label('title', __('Meta Keywords'), ['class' => 'form-label text-center']) }}
                                <input type="text" name="meta_keywords" class="form-control" id="meta_keywords" placeholder="{{ __('Meta Keywords') }}">
                            </div>

                            {{-- Meta Description --}}
                            <div class="col-md-4 col-sm-12 form-group">
                                {{ Form::label('description', __('Meta Description'), ['class' => 'form-label text-center']) }}
                                <textarea id="meta_description" name="meta_description" class="form-control" oninput="getWordCount('meta_description','meta_description_count','12.9px arial')" placeholder="{{ __('Meta Description') }}"></textarea>
                                <h6 id="meta_description_count">0</h6>
                            </div>

                            <div class="col-sm-12 col-md-12 text-end" style="margin-top:2%;">
                                {{ Form::submit(trans('Save'), ['class' => 'btn btn-primary me-1 mb-1']) }}
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if (has_permissions('read', 'categories'))
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <table class="table table-striped"
                                id="table_list" data-toggle="table" data-url="{{ url('categoriesList') }}"
                                data-click-to-select="true" data-responsive="true" data-side-pagination="server"
                                data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                                data-pagination-successively-size="3" data-query-params="queryParams">
                                <thead class="thead-dark">
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                        <th scope="col" data-field="category" data-sortable="true" data-align="center">{{ __('Category') }}</th>
                                        <th scope="col" data-field="slug_id" data-visible="false" data-sortable="true" data-align="center">{{ __('Slug') }}</th>
                                        <th scope="col" data-field="image" data-formatter="imageFormatter" data-sortable="false" data-align="center">{{ __('Image') }}</th>
                                        <th scope="col" data-field="type" data-sortable="false" data-align="center">{{ __('Facilities') }}</th>
                                        <th scope="col" data-field="property_classification_text" data-sortable="true" data-align="center">{{ __('Property Classification') }}</th>
                                        <th scope="col" data-field="meta_title" data-sortable="true" data-align="center">{{ __('Meta Title') }}</th>
                                        <th scope="col" data-field="meta_description" data-sortable="true" data-align="center"> {{ __('Meta Description') }}</th>
                                        <th scope="col" data-field="meta_keywords" data-sortable="true" data-align="center">{{ __('Meta Keywords') }}</th>
                                        <th scope="col" data-field="status" data-sortable="false" data-formatter="enableDisableSwitchFormatter" data-align="center"> {{ __('Enable/Disable') }} </th>
                                        <th scope="col" data-field="operate" data-sortable="false" data-align="center" data-events="actionEvents"> {{ __('Action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    @endif

    <!-- EDIT MODEL MODEL -->
    <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="myModalLabel1">{{ __('Edit Categories') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('categories-update') }}" class="form-horizontal" enctype="multipart/form-data" method="POST" data-parsley-validate>
                        {{ csrf_field() }}

                        <input type="hidden" id="old_image" name="old_image">
                        <input type="hidden" id="edit_id" name="edit_id">
                        <input type="hidden" value="{{ system_setting('svg_clr') }}" id="svg_clr">
                        <div class="row">
                            <div class="col-m-6">

                                {{-- Category --}}
                                <div class="col-md-12 form-group mandatory mt-1">
                                    <label for="edit_category" class="form-label">{{ __('Category') }}</label>
                                    <input type="text" id="edit_category" class="form-control" placeholder="Name" name="edit_category" data-parsley-required="true">
                                </div>

                                {{-- Slug --}}
                                <div class="col-md-12 col-12 form-group">
                                    {{ Form::label('slug', __('Slug'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::text('slug', '', [ 'class' => 'form-control ', 'placeholder' =>  __('Slug'), 'id' => 'edit-slug', ]) }}
                                    <small class="text-danger text-sm">{{ __("Only Small English Characters, Numbers And Hypens Allowed") }}</small>
                                </div>

                                {{-- Property Classification --}}
                                <div class="col-md-12 col-sm-12 form-group">
                                    {{ Form::label('property_classification', __('Property Classification'), ['class' => 'form-label text-center']) }}
                                    <select name="edit_property_classification" id="edit_property_classification" class="form-control form-select">
                                        <option value="1">{{ __('Sell/Long Term Rent') }}</option>
                                        <option value="2">{{ __('Commercial') }}</option>
                                        <option value="3">{{ __('New Project') }}</option>
                                        <option value="4">{{ __('Vacation Homes') }}</option>
                                        <option value="5">{{ __('Hotel Booking') }}</option>
                                    </select>
                                </div>

                                {{-- Meta Title --}}
                                <div class="col-md-12 col-sm-12 form-group">
                                    {{ Form::label('title', __('Meta Title'), ['class' => 'form-label text-center']) }}
                                    <input type="text" name="edit_meta_title" class="form-control" id="edit_meta_title" oninput="getWordCount('edit_meta_title','edit_meta_title_count','19.9px arial')" placeholder="{{ __('Meta title') }}">
                                    <h6 id="edit_meta_title_count">0</h6>
                                </div>

                                {{-- Meta Description --}}
                                <div class="col-md-12 col-sm-12 form-group mt-1">
                                    {{ Form::label('description', __('Description'), ['class' => 'form-label text-center']) }}
                                    <textarea id="edit_meta_description" name="edit_meta_description" class="form-control" style="height: 74px;" oninput="getWordCount('edit_meta_description','edit_meta_description_count','12.9px arial')"></textarea>
                                    <h6 id="edit_meta_description_count">0</h6>
                                </div>
                                <div class="col-md-12 col-sm-12 form-group">
                                    {{ Form::label('keywords', __('Keywords'), ['class' => 'form-label text-center']) }}

                                    {{ Form::text('edit_keywords', '', [
                                        'class' => 'form-control',
                                        'placeholder' => 'Keywords',
                                        'id' => 'edit_keywords',
                                    ]) }}

                                </div>

                            </div>

                            <div class="col-md-6">
                                <div class="col-sm-12 col-md-12 mandatory">
                                    {{ Form::label('type', __('Facilities'), ['class' => 'col-sm-12 col-form-label ']) }}

                                    <div id="output"></div>

                                    <select data-placeholder="Facilities" name="edit_parameter_type[]" id="edit_parameter_type" multiple class="form-select form-control mandatory">
                                        @foreach ($parameters as $parameter)
                                            <option value={{ $parameter->id }} id='op'>{{ $parameter->name }} {{ $parameter->is_required == 1 ? "*" : ""}}</option>
                                        @endforeach
                                    </select>
                                    @if (count($errors) > 0)
                                        @foreach ($errors->all() as $error)
                                            <div class="alert alert-danger error-msg">{{ $error }}</div>
                                        @endforeach
                                    @endif

                                </div>
                                 {{ Form::label('Sequence', __('Facilities Order & Preview'), ['class' => 'col-sm-12 col-form-label ']) }}
                                 <small class="text-muted d-block mb-2">
                                     <i class="bi bi-info-circle"></i> 
                                     {{ __('Drag and drop to reorder facilities. This is how they will appear in the frontend.') }}
                                 </small>

                                 <div class="col-sm-12 sequence">
                                     <div id="par" class="d-flex flex-wrap gap-2 p-3 border rounded" style="min-height: 60px; background-color: #f8f9fa;">
                                         <p class="text-muted m-0 w-100" id="edit-no-facilities-message">{{ __('No facilities selected. Please select facilities from the dropdown above.') }}</p>
                                     </div>
                                     <input type="hidden" name="update_seq" id="update_seq">
                                     
                                     <div class="alert alert-info mt-2" id="edit-facilities-preview-info" style="display: none;">
                                         <i class="bi bi-eye"></i>
                                         <strong>{{ __('Frontend Preview:') }}</strong> 
                                         <div id="edit-facilities-preview-text" class="mt-2"></div>
                                     </div>
                                     
                                     <div class="alert alert-success mt-2" id="edit-facilities-order-info" style="display: none;">
                                         <i class="bi bi-check-circle"></i>
                                         <strong>{{ __('Order Saved:') }}</strong> 
                                         <div id="edit-facilities-order-text" class="mt-2"></div>
                                     </div>
                                 </div>
                                <div class="col-sm-12" style="margin-top: 7%">

                                    {{ Form::label('image', __('Image'), ['class' => 'col-sm-12 col-form-label']) }}
                                    <input type="file" name="edit_image" id="edit_image" class="filepond" accept="image/svg+xml">
                                </div>
                                <div class="col-sm-12 text-center">
                                    <img id="blah" height="100" width="110" style="margin-left: 2%;" />
                                </div>

                            </div>

                        </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect"
                        data-bs-dismiss="modal">{{ __('Close') }}</button>

                    <button type="submit" class="btn btn-primary waves-effect waves-light">{{ __('Save') }}</button>
                    </form>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- EDIT MODEL -->
@endsection

@section('script')
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/dragula/3.6.6/dragula.min.js"
        integrity="sha512-MrA7WH8h42LMq8GWxQGmWjrtalBjrfIzCQ+i2EZA26cZ7OBiBd/Uct5S3NP9IBqKx5b+MMNH1PhzTsk6J9nPQQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script> --}}
    <script src=https://bevacqua.github.io/dragula/dist/dragula.js></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dragula@3.7.3/dist/dragula.min.css">
    <style>
        #par, #create_par {
            min-height: 60px;
        }
        .seq, .create-seq {
            transition: transform 0.2s ease;
            cursor: grab;
        }
        .seq:hover, .create-seq:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .seq:active, .create-seq:active {
            cursor: grabbing;
        }
        .gu-mirror {
            opacity: 0.8;
            cursor: grabbing !important;
        }
        .gu-transit {
            opacity: 0.2;
        }
        .create-seq .badge, .seq .badge {
            display: flex;
            align-items: center;
            padding: 0.5em 0.75em;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .create-seq .badge i, .seq .badge i {
            margin-right: 5px;
        }
    </style>
    <script>
        $(document).ready(function() {
            getWordCount("meta_title", "meta_title_count", "19.9px arial");
            getWordCount("meta_description", "meta_description_count", "12.9px arial");
            $('.add-category').hide();
            $('#select_parameter_type').chosen();
            $('#edit_parameter_type').chosen();

            // Initialize dragula for create form
            var createDragulaInstance = null;
            function initCreateDragula() {
                if (createDragulaInstance) {
                    createDragulaInstance.destroy();
                }
                
                var containers = [document.getElementById('create_par')];
                createDragulaInstance = dragula(containers, {
                    moves: function (el, source, handle, sibling) {
                        return el.classList.contains('create-seq');
                    }
                }).on('drag', function(el) {
                    el.style.opacity = '0.5';
                }).on('dragend', function(el) {
                    el.style.opacity = '1';
                }).on('drop', function(el, target, source, sibling) {
                    updateCreateSequence();
                });
            }

            function updateCreateSequence() {
                var sequence = [];
                var existingIDs = {};
                
                $('#create_par .create-seq').each(function() {
                    var id = $(this).attr('id');
                    if (!existingIDs[id]) {
                        existingIDs[id] = true;
                        sequence.push(id);
                    }
                });
                
                $('#create_seq').val(sequence.join(','));
                updateCreatePreview();
            }

            function updateCreatePreview() {
                var sequence = $('#create_seq').val();
                var previewText = '';
                var orderText = '';
                
                if (sequence) {
                    var ids = sequence.split(',');
                    var names = [];
                    var orderNumbers = [];
                    
                    ids.forEach(function(id, index) {
                        if (id && id !== '' && id !== 'no') {
                            var option = $('#select_parameter_type option[value="' + id + '"]');
                            if (option.length) {
                                var name = option.text().trim();
                                names.push(name);
                                orderNumbers.push((index + 1) + '. ' + name);
                            }
                        }
                    });
                    
                    previewText = '<strong>' + names.join(' → ') + '</strong>';
                    orderText = orderNumbers.join('<br>');
                }
                
                if (previewText) {
                    $('#facilities-preview-text').html(previewText);
                    $('#facilities-preview-info').show();
                    
                    $('#facilities-order-text').html(orderText);
                    $('#facilities-order-info').show();
                } else {
                    $('#facilities-preview-info').hide();
                    $('#facilities-order-info').hide();
                }
            }
            
            // Function to update edit form preview - shows how facilities will appear in frontend
            function updateEditPreview() {
                var sequence = $('#update_seq').val();
                var previewText = '';
                var orderText = '';
                
                if (sequence) {
                    var ids = sequence.split(',');
                    var names = [];
                    var orderNumbers = [];
                    
                    ids.forEach(function(id, index) {
                        if (id && id !== '' && id !== 'no') {
                            var option = $('#edit_parameter_type option[value="' + id + '"]');
                            if (option.length) {
                                var name = option.text().trim();
                                names.push(name);
                                orderNumbers.push((index + 1) + '. ' + name);
                            }
                        }
                    });
                    
                    previewText = '<strong>' + names.join(' → ') + '</strong>';
                    orderText = orderNumbers.join('<br>');
                }
                
                if (previewText) {
                    $('#edit-facilities-preview-text').html(previewText);
                    $('#edit-facilities-preview-info').show();
                    
                    $('#edit-facilities-order-text').html(orderText);
                    $('#edit-facilities-order-info').show();
                    
                    // Hide no facilities message
                    $('#edit-no-facilities-message').hide();
                } else {
                    $('#edit-facilities-preview-info').hide();
                    $('#edit-facilities-order-info').hide();
                    $('#edit-no-facilities-message').show();
                }
            }

            // Handle facilities selection in create form
            $('#select_parameter_type').on('change', function() {
                var selectedIds = $(this).val() || [];
                
                // Clear existing facilities
                $('#create_par').empty();
                $('#create_seq').val('');
                
                if (selectedIds.length === 0) {
                    $('#create_par').html('<p class="text-muted m-0 w-100" id="no-facilities-message">{{ __('No facilities selected. Please select facilities from the dropdown above.') }}</p>');
                    $('#facilities-preview-info').hide();
                    if (createDragulaInstance) {
                        createDragulaInstance.destroy();
                        createDragulaInstance = null;
                    }
                    return;
                }
                
                // Remove no facilities message
                $('#no-facilities-message').remove();
                
                // Add selected facilities in selection order
                selectedIds.forEach(function(id) {
                    var option = $('#select_parameter_type option[value="' + id + '"]');
                    var text = option.text().trim();
                    
                    $('#create_par').append(
                        '<div class="create-seq mb-2" id="' + id + '" style="cursor: move; user-select: none;">' +
                        '<span class="badge rounded-pill p-2" style="background:var(--bs-primary); display: inline-block; font-size: 0.9rem;">' +
                        '<span style="margin-right: 5px;">☰</span>' + text + '</span></div>'
                    );
                });
                
                // Update sequence
                updateCreateSequence();
                
                // Initialize dragula
                initCreateDragula();
            });

            // Select at least one classification by default
            if ($('input[name="property_classifications[]"]:checked').length === 0) {
                $('#classification1').prop('checked', true);
            }

            // Ensure at least one classification is selected
            $('input[name="property_classifications[]"]').on('change', function() {
                if ($('input[name="property_classifications[]"]:checked').length === 0) {
                    $(this).prop('checked', true);
                    alert('At least one property classification must be selected.');
                }
            });

            // Form validation before submission
            $('form').on('submit', function(e) {
                if ($('input[name="property_classifications[]"]:checked').length === 0) {
                    e.preventDefault();
                    alert('Please select at least one property classification.');
                    return false;
                }
                
                // Ensure facilities are selected and ordered for create form
                if ($(this).attr('action') && $(this).attr('action').includes('categories.store')) {
                    var selectedFacilities = $('#select_parameter_type').val();
                    if (!selectedFacilities || selectedFacilities.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one facility.');
                        return false;
                    }
                    
                    // Ensure create_seq is set (use selection order if not set by drag-and-drop)
                    if (!$('#create_seq').val() && selectedFacilities.length > 0) {
                        $('#create_seq').val(selectedFacilities.join(','));
                    }
                }
                
                return true;
            });
        });


        $('.add-category-button').on('click', function() {
            $('.add-category').toggle();
        })

        function queryParams(p) {
            return {
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                limit: p.limit,
                search: p.search
            };
        }

        $('#edit_parameter_type').on('change', function(e) {
                e.preventDefault();

                var selectedIds = $(this).val() || [];
                var currentSequence = $('#update_seq').val();
                var existingOrder = currentSequence ? currentSequence.split(',') : [];
                
                // Get currently displayed facility IDs
                var currentDisplayedIds = [];
                $('#par .seq').each(function() {
                    currentDisplayedIds.push($(this).attr('id'));
                });

                // Remove facilities that are no longer selected
                $('#par .seq').each(function() {
                    var id = $(this).attr('id');
                    if ($.inArray(id, selectedIds) === -1) {
                        $(this).remove();
                    }
                });

                // Update existing order to only include selected facilities
                var preservedOrder = existingOrder.filter(function(id) {
                    return $.inArray(id, selectedIds) !== -1;
                });

                // Find new facilities (selected but not in current display)
                var newFacilities = selectedIds.filter(function(id) {
                    return $.inArray(id, currentDisplayedIds) === -1;
                });

                // Build the final order: preserved order first, then new facilities
                var finalOrder = preservedOrder.slice(); // Copy preserved order
                newFacilities.forEach(function(id) {
                    if ($.inArray(id, finalOrder) === -1) {
                        finalOrder.push(id);
                    }
                });

                // If no preserved order exists, use selection order
                if (preservedOrder.length === 0 && existingOrder.length === 0) {
                    finalOrder = selectedIds.slice();
                }

                // Clear and rebuild in correct order
                $('#par').empty();

                finalOrder.forEach(function(id) {
                    if ($.inArray(id, selectedIds) !== -1) {
                        var option = $('#edit_parameter_type option[value="' + id + '"]');
                        var text = option.text().trim();
                        
                        if (text) {
                            $('#par').append($(
                                '<div class="seq mb-2" id="' + id + '" style="cursor: move; user-select: none;">' +
                                '<span class="badge rounded-pill p-2" style="background:var(--bs-primary); display: inline-block; font-size: 0.9rem;">' +
                                '<span style="margin-right: 5px;">☰</span>' + text + '</span></div>'
                            ));
                        }
                    }
                });

                // Update sequence
                $('#update_seq').val(finalOrder.join(','));
                
                // Update preview
                updateEditPreview();
                
                // Hide/show no facilities message
                if (selectedIds.length > 0) {
                    $('#edit-no-facilities-message').hide();
                } else {
                    $('#edit-no-facilities-message').show();
                }

                // Re-initialize dragula after facilities change
                if (window.dragulaInstance) {
                    window.dragulaInstance.destroy();
                }

                var containers = [document.getElementById('par')];
                window.dragulaInstance = dragula(containers, {
                    moves: function (el, source, handle, sibling) {
                        return el.classList.contains('seq');
                    }
                }).on('drag', function(el) {
                    el.style.opacity = '0.5';
                }).on('dragend', function(el) {
                    el.style.opacity = '1';
                }).on('drop', function(el, target, source, sibling) {
                    var sequence = [];
                    var existingIDs = {};

                    $('#par .seq').each(function() {
                        var id = $(this).attr('id');

                        if (!existingIDs[id]) {
                            existingIDs[id] = true;
                            sequence.push(id);
                        }
                    });

                    $('#update_seq').val(sequence.join(','));
                    updateEditPreview();
                });

            }

        );
        window.actionEvents = {
            'click .edit_btn': function(e, value, row, index) {
                $("#edit_id").val(row.id);

                $("#edit_category").val(row.category);
                $("#edit-slug").val(row.slug_id);
                $("#edit_property_classification").val(row.property_classification);

                getWordCount("edit_meta_title", "edit_meta_title_count", "19.9px arial");
                getWordCount(
                    "edit_meta_description",
                    "edit_meta_description_count",
                    "12.9px arial"
                );
                var sequence = [];
                $('.seq').empty();
                $('#update_seq').val('');
                $('#par').empty();
                $('#edit_parameter_type_chosen').css('width', '470px');

                $("#edit_meta_title").val(row.meta_title);
                $("#edit_meta_description").val(row.meta_description);
                $("#edit_keywords").val(row.meta_keywords);
                $('#blah').attr('src', row.image);
                $('#edit_image').attr('src', row.image);
                $("#sequence").val(row.type);

                var type = row.parameter_types || '';

                var type_arr = (type && type !== '') ? type.split(',') : [];


                if (type != '') {
                    $('#edit_parameter_type').val(type.split(',')).trigger('change');
                } else {
                    $('#edit_parameter_type').val('');
                }

                // Clear and rebuild in the saved order
                $('#par').empty();
                str = '';

                val_arr = $("#edit_parameter_type").val() || [];

                // Use the saved order (type_arr) to maintain the correct sequence
                // Only show facilities that are currently selected
                if (type_arr && type_arr.length > 0) {
                    // Build facilities in saved order, filtering to only selected ones
                    type_arr.forEach(function(v) {
                        if (v != '' && v != 'no' && $.inArray(v, val_arr) !== -1) {
                            text_op = ($('#edit_parameter_type option[value="' + v + '"]').text());
                            if (text_op) {
                                $('#par').append($(
                                    '<div class="seq mb-2" id="' + v + '" style="cursor: move; user-select: none;">' +
                                    '<span class="badge rounded-pill p-2" style="background:var(--bs-primary); display: inline-block; font-size: 0.9rem;">' +
                                    '<span style="margin-right: 5px;">☰</span>' + text_op + '</span></div>'
                                ));
                                str += v + ',';
                            }
                        }
                    });
                    
                    // Add any newly selected facilities that weren't in the saved order (append at end)
                    val_arr.forEach(function(v) {
                        if ($.inArray(v, type_arr) === -1) {
                            text_op = ($('#edit_parameter_type option[value="' + v + '"]').text());
                            if (text_op) {
                                $('#par').append($(
                                    '<div class="seq mb-2" id="' + v + '" style="cursor: move; user-select: none;">' +
                                    '<span class="badge rounded-pill p-2" style="background:var(--bs-primary); display: inline-block; font-size: 0.9rem;">' +
                                    '<span style="margin-right: 5px;">☰</span>' + text_op + '</span></div>'
                                ));
                                str += v + ',';
                            }
                        }
                    });
                } else {
                    // No saved order, use selection order
                    $("#edit_parameter_type :selected").each(function(key, value) {
                        text_op = ($('#edit_parameter_type option[value="' + this.value + '"]').text());
                        if (text_op) {
                            $('#par').append($(
                                '<div class="seq mb-2" id="' + this.value + '" style="cursor: move; user-select: none;">' +
                                '<span class="badge rounded-pill p-2" style="background:var(--bs-primary); display: inline-block; font-size: 0.9rem;">' +
                                '<span style="margin-right: 5px;">☰</span>' + text_op + '</span></div>'
                            ));
                            str += this.value + ',';
                        }
                    });
                }

                $("#edit_parameter_type").val(str.split(',')).trigger('chosen:updated');

                // Initialize sequence from displayed order
                var sequence = [];
                $('#par .seq').each(function() {
                    var id = $(this).attr('id');
                    if (id && $.inArray(id, sequence) === -1) {
                        sequence.push(id);
                    }
                });

                $('#update_seq').val(sequence.join(','));
                
                // Update preview
                updateEditPreview();

                // Destroy existing dragula instance if it exists
                if (window.dragulaInstance) {
                    window.dragulaInstance.destroy();
                }

                // Initialize dragula for drag and drop
                var containers = [document.getElementById('par')];

                window.dragulaInstance = dragula(containers, {
                    moves: function (el, source, handle, sibling) {
                        return el.classList.contains('seq');
                    }
                }).on('drag', function(el) {
                    el.style.opacity = '0.5';
                }).on('dragend', function(el) {
                    el.style.opacity = '1';
                }).on('drop', function(el, target, source, sibling) {
                    var sequence = [];
                    var existingIDs = {};

                    $('#par .seq').each(function() {
                        var id = $(this).attr('id');

                        if (!existingIDs[id]) {
                            existingIDs[id] = true;
                            sequence.push(id);
                        }
                    });

                    $('#update_seq').val(sequence.join(','));
                    updateEditPreview();
                });


            }
        }



        var sequence = [];
        $('.seq').each(function() {

            sequence.push($(this).attr('id'));
        });

        $('#update_seq').val(sequence.toString());
        document.getElementById('output').innerHTML = location.search;
        $(".chosen-select").chosen();

        $('.bottomleft').click(function() {
            $('#edit_image').click();
        });


        $("#category").on('keyup',function(e){
            let category = $(this).val();
            let slugElement = $("#slug");
            if(category){
                $.ajax({
                    type: 'POST',
                    url: "{{ route('category.generate-slug') }}",
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        category: category
                    },
                    beforeSend: function() {
                        slugElement.attr('readonly', true).val('Please wait....')
                    },
                    success: function(response) {
                        if(!response.error){
                            if(response.data){
                                slugElement.removeAttr('readonly').val(response.data);
                            }else{
                                slugElement.removeAttr('readonly').val("")
                            }
                        }
                    }
                });
            }else{
                slugElement.removeAttr('readonly', true).val("")
            }
        });

        $("#edit_category").on('keyup',function(e){
            let editCategory = $(this).val();
            let id = $("#edit_id").val();
            let slugElement = $("#edit-slug");
            if(editCategory){
                $.ajax({
                    type: 'POST',
                    url: "{{ route('category.generate-slug') }}",
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        category: editCategory,
                        id: id
                    },
                    beforeSend: function() {
                        slugElement.attr('readonly', true).val('Please wait....')
                    },
                    success: function(response) {
                        if(!response.error){
                            if(response.data){
                                slugElement.removeAttr('readonly').val(response.data);
                            }else{
                                slugElement.removeAttr('readonly', true).val("")
                            }
                        }
                    }
                });
            }else{
                slugElement.removeAttr('readonly', true).val("")
            }
        });

        // Handle category delete
        $(document).on('click', '.delete_btn', function(e) {
            e.preventDefault();
            const categoryId = $(this).data('id');
            const categoryName = $(this).closest('tr').find('td:eq(1)').text().trim();

            Swal.fire({
                title: '{{ __("Are you sure?") }}',
                text: `{{ __("You are about to delete category") }}: "${categoryName}". {{ __("This action cannot be undone!") }}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("Yes, delete it!") }}',
                cancelButtonText: '{{ __("Cancel") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url("categories") }}/' + categoryId,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.error === false) {
                                Swal.fire({
                                    title: '{{ __("Deleted!") }}',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: '{{ __("OK") }}'
                                }).then(() => {
                                    $('#table_list').bootstrapTable('refresh');
                                });
                            } else {
                                Swal.fire(
                                    '{{ __("Error!") }}',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = '{{ __("Something went wrong!") }}';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire(
                                '{{ __("Error!") }}',
                                errorMessage,
                                'error'
                            );
                        }
                    });
                }
            });
        });
    </script>
@endsection
