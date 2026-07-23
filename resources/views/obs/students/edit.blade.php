@extends('obs.layouts.app')

@section('title','Öğrenci Düzenle')

@section('content')

<div class="card shadow">


    <div class="card-header bg-warning">

        <h4 class="mb-0">

            <i class="bi bi-pencil-square"></i>
            Öğrenci Düzenle

        </h4>

    </div>



    <div class="card-body">



        @if($errors->any())

            <div class="alert alert-danger">


                <ul class="mb-0">


                    @foreach($errors->all() as $error)


                        <li>
                            {{ $error }}
                        </li>


                    @endforeach


                </ul>


            </div>


        @endif





        <form action="{{ route('obs.students.update',$student->student_no) }}"
              method="POST">


            @csrf

            @method('PUT')





            <div class="mb-3">


                <label class="form-label">
                    Öğrenci No
                </label>



                <input
                    type="text"
                    class="form-control"
                    value="{{ $student->student_no }}"
                    disabled>


            </div>






            <div class="mb-3">


                <label class="form-label">
                    Adı
                </label>



                <input
                    type="text"
                    name="student_name"
                    class="form-control"
                    value="{{ old('student_name',$student->student_name) }}"
                    required>


            </div>






            <div class="mb-3">


                <label class="form-label">
                    Soyadı
                </label>



                <input
                    type="text"
                    name="student_surname"
                    class="form-control"
                    value="{{ old('student_surname',$student->student_surname) }}"
                    required>


            </div>







            <button class="btn btn-primary">


                <i class="bi bi-save"></i>

                Güncelle


            </button>





            <a href="{{ route('obs.students.index') }}"
               class="btn btn-secondary">


                <i class="bi bi-arrow-left"></i>

                Geri


            </a>





        </form>



    </div>


</div>


@endsection
