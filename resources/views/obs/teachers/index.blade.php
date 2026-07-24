@extends('obs.layouts.app')

@section('title','Öğretmen Yönetimi')

@section('content')

<div class="card shadow">


   <div class="card-header">
    <h4 class="mb-0">
        <i class="bi bi-person-badge-fill"></i>
        Öğretmen Listesi
    </h4>
</div>


    <div class="card-body">

        @if(session('success'))


            <div class="alert alert-success">


                {{ session('success') }}


            </div>


        @endif






        <form action="{{ route('obs.teachers.index') }}"
              method="GET"
              class="mb-3">



            <div class="input-group">



                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="TC, Ad veya Soyad ile ara..."
                    value="{{ $search ?? '' }}">





                <button class="btn btn-primary">


                    Ara


                </button>




            </div>



        </form>







        <div class="table-responsive">



            <table class="table table-bordered table-hover">



                <thead class="table-dark">



                    <tr>


                        <th>TC Kimlik</th>

                        <th>Adı</th>

                        <th>Soyadı</th>

                        <th>E-Posta</th>

                        <th width="180">
                            İşlemler
                        </th>


                    </tr>



                </thead>






                <tbody>



                @forelse($teachers as $teacher)



                    <tr>



                        <td>
                            {{ $teacher->tc_no }}
                        </td>



                        <td>
                            {{ $teacher->name }}
                        </td>



                        <td>
                            {{ $teacher->surname }}
                        </td>



                        <td>
                            {{ $teacher->email ?? '-' }}
                        </td>




                        <td>




                            <a href="{{ route('obs.teachers.edit',$teacher->id) }}"
                               class="btn btn-warning btn-sm">


                                <i class="bi bi-pencil"></i>

                                Düzenle


                            </a>







                            <form action="{{ route('obs.teachers.destroy',$teacher->id) }}"
                                  method="POST"
                                  class="d-inline">



                                @csrf

                                @method('DELETE')




                                <button
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Öğretmen silinsin mi?')">



                                    <i class="bi bi-trash"></i>

                                    Sil



                                </button>




                            </form>





                        </td>



                    </tr>





                @empty



                    <tr>


                        <td colspan="5" class="text-center">


                            Henüz öğretmen bulunmuyor.


                        </td>


                    </tr>



                @endforelse




                </tbody>



            </table>




        </div>





        <div class="mt-3">

            {{ $teachers->links() }}

        </div>




    </div>



</div>


@endsection
