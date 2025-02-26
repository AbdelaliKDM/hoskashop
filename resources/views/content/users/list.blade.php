@extends('layouts/contentNavbarLayout')

@section('title', __('Users'))

@section('content')

<h4 class="fw-bold py-3 mb-3">
  <span class="text-muted fw-light">{{__('Users')}} /</span> {{__('Browse users')}}
</h4>

<!-- Basic Bootstrap Table -->
<div class="card">
  <div class="table-responsive text-nowrap">
    <div class="table-header row justify-content-between">
      <h5 class="col-md-auto">{{__('Users table')}}</h5>
    </div>
    <table class="table" id="laravel_datatable">
      <thead>
        <tr>
          <th>#</th>
          <th>{{__('Name')}}</th>
          <th>{{__('userType')}}</th>
          <th>{{__('Phone')}}</th>
          <th>{{__('Email')}}</th>
          <th>{{__('Status')}}</th>
          <th>{{__('Created at')}}</th>
          <th>{{__('Actions')}}</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<div class="modal fade" id="modal" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h4 class="fw-bold py-1 mb-1">{{ __('UserType') }}</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
              <input type="text" id="form_type" hidden />
              <input type="text" class="form-control" id="id" name="id" hidden />
              <form class="form-horizontal" onsubmit="event.preventDefault()" action="#"
                  enctype="multipart/form-data" id="form">

                  <div class="mb-3">
                      <label class="form-label" for="user_type">{{ __('UserType') }}</label>
                      <select class="form-select" name="user_type_id" id="user_type">

                      </select>
                  </div>




                  <div class="mb-3" style="text-align: center">
                      <button type="submit" id="submit" name="submit"
                          class="btn btn-primary">{{ __('Send') }}</button>
                  </div>

              </form>
          </div>
      </div>
  </div>
</div>
@endsection


@section('page-script')
<script>
  $(document).ready(function(){
    load_data();
    function load_data() {
        //$.fn.dataTable.moment( 'YYYY-M-D' );
        var table = $('#laravel_datatable').DataTable({
          language:  {!! file_get_contents(base_path('lang/'.session('locale','en').'/datatable.json')) !!},
            responsive: true,
            processing: true,
            serverSide: true,
            pageLength: 10,

            ajax: {
                url: "{{ url('user/list') }}",
            },

            type: 'GET',

            columns: [

                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex'
                },

                {
                    data: 'name',
                    name: 'name'
                },

                {
                    data: 'phone',
                    name: 'phone'
                },


                {
                    data: 'email',
                    name: 'email'
                },


                {
                    data: 'status',
                    name: 'status',
                    render: function(data){
                          if(data == false){
                              return '<span class="badge bg-danger">{{__("Inactive")}}</span>';
                            }else{
                              return '<span class="badge bg-success">{{__("Active")}}</span>';
                            }
                          }
                },

                {
                    data: 'created_at',
                    name: 'created_at'
                },

                {
                    data: 'action',
                    name: 'action',
                    searchable: false
                }

            ]
        });
    }

    $(document.body).on('click', '.delete', function() {

      var user_id = $(this).attr('table_id');

      Swal.fire({
        title: "{{ __('Warning') }}",
        text: "{{ __('Are you sure?') }}",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: "{{ __('Yes') }}",
        cancelButtonText: "{{ __('No') }}"
      }).then((result) => {
        if (result.isConfirmed) {

          $.ajax({
            url: "{{ url('user/update') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type:'POST',
            data:{
              user_id : user_id,
              status : 0
            },
            dataType : 'JSON',
            success:function(response){
                if(response.status==1){

                  Swal.fire(
                    "{{ __('Success') }}",
                    "{{ __('success') }}",
                    'success'
                  ).then((result)=>{
                    $('#laravel_datatable').DataTable().ajax.reload();
                  });
                }
              }
          });


        }
      })
      });

      $(document.body).on('click', '.restore', function() {

      var user_id = $(this).attr('table_id');

      Swal.fire({
        title: "{{ __('Warning') }}",
        text: "{{ __('Are you sure?') }}",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: "{{ __('Yes') }}",
        cancelButtonText: "{{ __('No') }}"
      }).then((result) => {
        if (result.isConfirmed) {

          $.ajax({
            url: "{{ url('user/update') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type:'POST',
            data:{
              user_id : user_id,
              status : 1
            },
            dataType : 'JSON',
            success:function(response){
                if(response.status==1){

                  Swal.fire(
                    "{{ __('Success') }}",
                    "{{ __('success') }}",
                    'success'
                  ).then((result)=>{
                    $('#laravel_datatable').DataTable().ajax.reload();
                  });
                }
              }
          });


        }
      })
      });



      $(document.body).on('click', '.update', function() {
                document.getElementById('form').reset();
                document.getElementById('form_type').value = "update";
                var user_id = $(this).attr('table_id');
                $("#id").val(user_id);

                $.ajax({
                    url: '{{ url("user/update") }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: 'POST',
                    data: {
                      user_id: user_id
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        if (response.status == 1) {
                            var userType = response.data.user_type_id;
                            var userTypesHtml = '';
                            @foreach ($userTypes as $ut)
                              if("{{$ut->id}}" == userType){
                                userTypesHtml += '<option value="{{$ut->id}}" selected>{{$ut->name_ar}}</option>';
                              }else{
                                userTypesHtml += '<option value="{{$ut->id}}">{{$ut->name_ar}}</option>';
                              }
                            @endforeach
                            $('#user_type').html(userTypesHtml);
                            $("#modal").modal("show");
                        }
                    }
                });
            });



            $('#submit').on('click', function() {

                var formdata = new FormData($("#form")[0]);
                var formtype = document.getElementById('form_type').value;
                console.log(formtype);
                if (formtype == "create") {
                    url = "{{ url('user/create') }}";
                }

                if (formtype == "update") {
                    url = "{{ url('user/update') }}";
                    formdata.append("user_id", document.getElementById('id').value)
                }

                $("#modal").modal("hide");


                $.ajax({
                    url: url,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: 'POST',
                    data: formdata,
                    dataType: 'JSON',
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.status == 1) {
                            Swal.fire({
                                title: "{{ __('Success') }}",
                                text: "{{ __('success') }}",
                                icon: 'success',
                                confirmButtonText: 'Ok'
                            }).then((result) => {
                                location.reload();
                            });
                        } else {
                            console.log(response.message);
                            Swal.fire(
                                "{{ __('Error') }}",
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(data) {
                        var errors = data.responseJSON;
                        console.log(errors);
                        Swal.fire(
                            "{{ __('Error') }}",
                            errors.message,
                            'error'
                        );
                        // Render the errors with js ...
                    }
                });
});
  });



</script>
@endsection
