@extends('layouts.main')

@section('title')
    {{ __('Email Templates') }}
@endsection


@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Email Templates') }}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">

                    <section class="section">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <table class="table table-striped"
                                            id="table_list" data-toggle="table" data-url="{{ route('email-templates.list') }}"
                                            data-click-to-select="false" data-responsive="true" data-side-pagination="server"
                                            data-pagination="false" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false"
                                            data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                            data-trim-on-search="false" data-sort="false" data-sort-name="id" data-sort-order="desc"
                                            data-pagination-successively-size="3" data-query-params="queryParams">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th scope="col" data-field="no" data-align="center" data-width="80">{{ __('No') }}</th>
                                                    <th scope="col" data-field="title">{{ __('Title') }}</th>
                                                    <th scope="col" data-field="operate" data-sortable="false" data-align="center" data-width="100"> {{ __('Action') }}</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
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

        // Add section headers after table loads
        $(document).ready(function() {
            var categoryLabels = {
                'main': 'Main / General Emails',
                'vacation_home': 'Vacation Home Emails',
                'hotel': 'Hotel Emails'
            };

            function addSectionHeaders() {
                try {
                    var tableData = $('#table_list').bootstrapTable('getData');
                    if (!tableData || tableData.length === 0) {
                        return;
                    }
                    
                    var prevCategory = '';
                    var $tbody = $('#table_list tbody');
                    
                    // Remove existing section headers
                    $tbody.find('.section-header-row').remove();
                    
                    // Add section headers before first item of each category
                    $tbody.find('tr').each(function(index) {
                        var $row = $(this);
                        // Skip if it's already a section header
                        if ($row.hasClass('section-header-row')) {
                            return;
                        }
                        
                        if (tableData[index] && tableData[index].category) {
                            var category = tableData[index].category;
                            
                            if (category !== prevCategory) {
                                prevCategory = category;
                                var sectionLabel = categoryLabels[category] || category;
                                var $headerRow = $('<tr class="section-header-row" style="background-color: #f8f9fa !important; font-weight: bold; border-top: 2px solid #dee2e6; border-bottom: 2px solid #dee2e6;"><td colspan="3" style="padding: 15px; text-align: center; color: #2c3e50; font-size: 16px;">━━━ ' + sectionLabel + ' ━━━</td></tr>');
                                $row.before($headerRow);
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error adding section headers:', e);
                }
            }

            $('#table_list').on('load-success.bs.table', function() {
                setTimeout(addSectionHeaders, 200);
            });

            $('#table_list').on('refresh.bs.table', function() {
                setTimeout(addSectionHeaders, 200);
            });
            
            // Also try after a delay in case events don't fire
            setTimeout(function() {
                if ($('#table_list tbody tr').length > 0) {
                    addSectionHeaders();
                }
            }, 500);
        });
    </script>
@endsection
