<?php

namespace App\Http\Controllers\Admin\Company;

use App\Empresa;
use App\Facades\Core;
use App\Tienda;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\URL;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Controlamos para que se muestre un formulario de crear empresa
     * y tambien forzamos a que se carge la información del usuario logueado
     *
     * @return \Illuminate\Http\Response
     */
    public function index($nameFirstName)
    {
        $userPerfil = Core::getUserPerfil();
        $userContacto = Core::getUserContact();
        $perfil = $userPerfil;
        $contacto = $userContacto;

        // Nos aseguramos de que la ruta sea la del usuario logueado
        if ( $nameFirstName != $userPerfil[0]->perfil_route)
            return \Redirect::route('companies.index', $userPerfil[0]->perfil_route);

        Core::isRouteValid($userPerfil[0]->perfil_route);

        // Saber si el usuario tiene empresa
        $empresa = Empresa::where('users_id', \Auth::user()->id)->first();

        if ($empresa === null) {
            return view('admin.company.new-company', compact('perfil', 'contacto', 'userPerfil', 'userContacto'));

        } else {
            $countTiendas = Tienda::where('empresa_id', $empresa->id)->count();
            return view('admin.company.company', compact('perfil', 'contacto', 'userPerfil', 'userContacto', 'empresa', 'countTiendas'));
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->ajax() ) {

            // GET AND SAVE PHOTO COMPANY
            $filePhotoCompany = $request->file('logo');
            $namePhotoCompany = 'company-'.\Auth::user()->id . Carbon::now()->second . $filePhotoCompany->getClientOriginalName();
            \Storage::disk('photo_company')->put($namePhotoCompany, \File::get($filePhotoCompany));

            // Generte data
            $company = new Empresa([
                'users_id'       => \Auth::user()->id,
                'estado'        => '1',
                'logo'          => $namePhotoCompany,
                'nombre'        => $request['nombre'],
                'rfc'           => $request['rfc'],
                'pagina_web'    => $request['pagina_web'],
                'giro_empresa'  => $request['giro_empresa'],
                'sector'        => $request['sector'],
                'direccion'     => $request['direccion'],
                'tel'           => $request['tel'],
                'fax'           => $request['fax'],
                'correo'        => $request['correo'],
                'idioma'        => $request['idioma'],
                'pais'          => $request['pais']
            ]);

            // Save company
            if ( $company->save() ) {

                $userPerfil = Core::getUserPerfil();

                return response()->json([
                    'message' => 'Empresa dada de alta.',
                    'url'       => route('companies.index', $userPerfil[0]->perfil_route)
                ]);
            }
        }

        abort(404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if ($request->ajax() ) {

            $empresa = Empresa::where('users_id', \Auth::user()->id)->first();

            return response()->json([
                'company' => $empresa
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Actualizar una empresa, comprobar si la petición es AJAX y si trae
     * una imagen para eliminar la actual y agregar la nueva
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {

        if ($request->ajax() ) {

            $empresa = Empresa::findOrFail($request['id']);
            $empresa->fill($request->all());

            if ($request->file('logo') )
            {
                // Guardar la nueva imagen en el disco
                $filePhotoCompany = $request->file('logo');
                $namePhotoCompany = 'company-'.\Auth::user()->id . Carbon::now()->second . $filePhotoCompany->getClientOriginalName();
                \Storage::disk('photo_company')->put($namePhotoCompany, \File::get($filePhotoCompany));

                $nameImgToDelete = $empresa->logo;
                $empresa->logo = $namePhotoCompany;

                // ELIMINAR LA VIEJA IMAGEN DEL DISCO DURO
            }


            $empresa->save();

            // Obtener nombre y ruta del logo de la imagen
            $empresa->logo = URL::to('/') . '/media/photo-company/' . $empresa->logo;

            return response()->json([
                'company' => $empresa
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


}