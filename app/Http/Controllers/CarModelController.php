<?php

namespace App\Http\Controllers;

use App\Models\CarModel;
use App\Repositories\CarModelRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarModelController extends Controller
{
    public function __construct(CarModel $carModel)
    {
        $this->carModel = $carModel; 
    }

    /**
     * Display a listing of the resource.
     * 
     *@param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $carModelRepo = new CarModelRepository($this->carModel);

        if($request->has('brand_attributes')){
            $brand_attributes = $request->get('brand_attributes');
            $carModelRepo->selectAttributesRegisterRelated('brand:id,'.$brand_attributes);
        } else {
            $carModelRepo->selectAttributesRegisterRelated('brand');
        }

        if($request->has('query')){
            $carModelRepo->queryFilter($request->get('query'));
        }

        if($request->has('attributes')){
            $carModelRepo->SelectAttributes($request->get('attributes'));
        } 

        return response()->json($carModelRepo->getResult(), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->carModel->rules());
        $image = $request->file('image');
        $image_urn = $image->store('images/car_models', 'public');
        $carModel = $this->carModel->create([
            'brand_id' => $request->brand_id,
            'name' => $request->name,
            'image' => $image_urn,
            'port_number' => $request->port_number,
            'seats' => $request->seats,
            'air_bag' => $request->air_bag,
            'abs' => $request->abs,
        ]);

        return response()->json($carModel, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $carModel = $this->carModel->with('brand')->find($id);
        if($carModel === null) {
            return response()->json(['error' => 'Não foi possível encontrar esta marca!'], 404);
        }

        return response()->json($carModel, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $carModel = $this->carModel->find($id);
        if($carModel === null) {
            return response()->json(['error' => 'Não foi possível atualizar esta marca!'], 404);
        }
        if($request->method() === 'PATCH') {
            $dynamicRules = array();
            //Percorre as regras definidas no Model
            foreach($carModel as $input => $rule) {
                //Coleta apenas as regras aplicáveis nos parametros recebidos
                if(array_key_exists($input, $request->all())){
                    $dynamicRules[$input] = $rule;
                }
            }
            $request->validate($dynamicRules);
        } else {
            $request->validate($carModel->rules());
        }
        //Remove a imagem anterior
        if($request->file('image')) {
            Storage::disk('public')->delete($carModel->image);
        }
        $image = $request->file('image');
        $image_urn = $image->store('images/car_models', 'public');
        $carModel->fill($request->all());
        $carModel->image = $image_urn;

        $carModel->save();
        return response()->json($carModel, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $carModel = $this->carModel->find($id);
        if($carModel === null) {
            return response()->json(['error' => 'Não foi possível apagar esta marca!'], 404);
        }
        //Remove a imagem anterior
        Storage::disk('public')->delete($carModel->image);
        $carModel->delete();

        return response()->json(['msg' => 'Modelo deletado com sucesso!'], 200);
    }
}
