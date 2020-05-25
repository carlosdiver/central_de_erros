<?php

namespace App\Http\Controllers\Api;

use App\Erro;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

const SEARCH_FOR_LEVEL = "1";
const SEARCH_FOR_DESCRIPTION = "2";
const SEARCH_FOR_ORIGIN = "2";

class ErroController extends Controller
{
  /**
   * @var Erro
   */
  private $erro;

  public function __construct(Erro $erro)
  {
    $this->erro = $erro;
    $this->middleware('jwt.auth');
  }

  private function ObterIdFrequencia($data): string
  {
    $strFreq = '';
    if ($data['titulo'] !== null) 
      $strFreq .= $data['titulo'];
    
    if ($data['descricao'] !== null) 
      $strFreq .= $data['descricao'];            

    if ($data['nivel'] !== null) 
      $strFreq .= $data['nivel'];            

    if ($data['ambiente'] !== null) 
      $strFreq .= $data['ambiente'];                        

    if ($data['origem'] !== null) 
      $strFreq .= $data['origem'];             

    if ($data['data'] !== null) 
      $strFreq .= $data['data']; 

    if ($data['usuario_id'] !== null)
      $strFreq .= $data['usuario_id'];   

    return md5($strFreq);
  }

  public function index(Request $request)
  {    
    $userId  = auth('api')->user()->id;

    $ambiente   = $request->get('ambiente');
    $ordenacao  = $request->get('order');
    $nivel      = $request->get('nivel');
    $descricao  = $request->get('descricao');
    $origem     = $request->get('origem');

    $filters = [
      ['status', '=', 'ativo']
    ];

    if ($ambiente !== null)
      array_push($filters, ["ambiente", '=', $ambiente]);

      $order = 'eventos';
      $direcao = 'desc';
      if ($ordenacao !== null)
      {
        if ($ordenacao === '1')
        {
          $order = 'nivel';
          $direcao = 'asc';
        }
      }

    if ($nivel !== null)
        array_push($filters, ["nivel", 'LIKE', '%'.$nivel.'%']);

    if ($descricao !== null)
        array_push($filters, ["descricao", 'LIKE', '%'.$descricao.'%']);
        
    if ($origem !== null)
        array_push($filters, ["origem", 'LIKE', '%'.$origem.'%']);

    $erros = Erro::where($filters)
      ->orderBy($order, $direcao);


    return response()->json($erros->get(), 200);
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function save(Request $request)
  {
    $data = $request->all();

    $validator = \Validator::make($request->all(), [
      'titulo' => 'required',
      'descricao' => 'required',
      'nivel' => ['required', Rule::in(['error', 'warning', 'debug']), ],
      'ambiente' => ['required', Rule::in(['1', '2', '3']), ],
      'origem' => 'required'
      ], [
          'in'=>'Informe um :attribute válido!'
          ]);

    try
    {
      $validator->validate();

      $data['usuario_id'] = auth('api')->user()->id;
      $data['data'] = date('Y-m-d'); 

      $idFrequencia = $this->ObterIdFrequencia($data);  

      $erro = Erro::where([
        ['status', '=', 'Ativo'],
        ['id_frequencia', '=', $idFrequencia]]
      )->first();

      if ($erro === null)
      {
        $data['usuario_name'] = auth('api')->user()->name;
        $data['status'] = 'Ativo';
        $data['id_frequencia'] = $idFrequencia;
        $data['eventos'] = 1;

        Erro::create($data);

        return response()->json([
            'msg' => 'Log de Erro cadastrado com sucesso!'
        ], 200);
      }
      else
      {
        $erro->eventos += 1;
        $erro->update();
      
        return response()->json([
          'msg' => 'Log de Erro atualizado com sucesso!'
        ], 200);
      }

    } catch (\Exception $e) {
      return response()->json( [
          'Erro' => 'Não foi possível cadastrar o log de erro.',
          'Msg' => $validator->errors()->all()[0]
      ], 400);
    }

    /*
    protected function storeOrUpdate(Request $request, $id = NULL) 
    {
        $data = $request->all();
        //sore
        
        if ($id == NULL)
        {
            try{
                $data['usuario_id'] = auth('api')->user()->id;
                $data['usuario_name'] = auth('api')->user()->name;
                $data['status'] = 'ativo';
                $data['data'] = date('Y-m-d');

                $erro = $this->erro->create($data);

                return response()->json([
                    'msg' => 'Log de Erro cadastrado com sucesso!'
                ], 200);

            } catch (\Exception $e) {
                return response()->json( [
                    'Erro' => 'Não foi possível cadastrar o log de erro.',
                    'Msg' => 'Verifique os dados e tente novamente!' . $e->getMessage()
                ], 400);
            }
        }

        //update     
        try{
            $erro = auth('api')->user()->erro()->findOrFail($id);
            $erro->update($data);

            return response()->json([
                'data' => [
                    'msg' => 'Log atualizado com sucesso!'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'Erro' => 'Erro ao atualizar: Log não existe ou pertence a outro usuário!',
                'Msg' => 'Verifique os dados e tente novamente!'
            ], 400);
        }        
    }
    */

    public function index(Request $request)
    {
        $userId         = auth('api')->user()->id;
        $ambience       = $request->get('ambiente');
        $ordination     = $request->get('ordenacao');
        $search         = $request->get('busca');
        $searchParam    = $request->get('chave');

        $filters = [
            ['status',      '=',    'ativo'],
            ['usuario_id',  '=',    $userId]];

        if ($ambience !== null)
            array_push($filters, ["ambiente", '=', $ambience]);


        if ($search !== null){
            if ($search === SEARCH_FOR_LEVEL)
                array_push($filters, ["nivel", '=', $searchParam]);

            if ($search === SEARCH_FOR_DESCRIPTION)
                array_push($filters, ["titulo", '=', $searchParam]);
                
            if ($search === SEARCH_FOR_ORIGIN)
                array_push($filters, ["origem", '=', $searchParam]);                
        }

        $order = "data";
        if ($ordination === "1")
            $order = "nivel";
   
        $erros = Erro::where($filters)
            ->orderBy($order, 'asc');

        return response()->json($erros->paginate(10), 200 );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       // $this->storeOrUpdate($request);
    
        $data = $request->all();

        try{
            $data['usuario_id'] = auth('api')->user()->id;
            $data['usuario_name'] = auth('api')->user()->name;
            $data['status'] = 'ativo';
            $data['data'] = date('Y-m-d');

            $erro = $this->erro->create($data);

            return response()->json([
                'msg' => 'Log de Erro cadastrado com sucesso!'
            ], 200);

        } catch (\Exception $e) {
            return response()->json( [
                'Erro' => 'Não foi possível cadastrar o log de erro.',
                'Msg' => 'Verifique os dados e tente novamente!' . $e->getMessage()
            ], 400);
        }
        
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    $userId  = auth('api')->user()->id;

    try{
      $erro = auth('api')->user()->erros()->findOrFail($id);
      return response()->json([
          'data' => $erro
      ], 200);

    } catch (\Exception $e) {
      return response()->json(
        ['Erro' => 'Log com ID ' . $id . ' não existe ou pertence a outro usuário!',
        'msg'=> $e->getMessage()], 
        404);
    }
  }

  /**
   * Change status of the specified resource to Concluded.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function store($id)
  {
    try{
      $erro = auth('api')->user()->erros()->findOrFail($id);
      if ($erro->status === 'Ativo') {
        $erro->status = 'Concluido';
        $erro->update();

        return response()->json([
            'msg' => 'Log de ID ' . $id  . ' arquivado com sucesso!'
        ], 200);
      } else {
        return response()->json([
            'msg' => 'Log de ID ' . $id  . ' já está arquivado!'
        ], 400);
      }

    } catch (\Exception $e) {
      return response()->json( [
        'Erro' => 'Não foi possível arquivar o log de ID ' . $id . '!',
        'Msg' => 'Verifique o ID passado e novamente!'
      ], 404);
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
    try {
      $erro = auth('api')->user()->erros()->findOrFail($id);
      $erro->delete();
      return response()->json([
          'msg' => 'Log de ID ' . $id  . ' excluído com sucesso!'
      ], 400);
    } catch (\Exception $e) {
      return response()->json( [
          'Erro' => 'Não foi possível excluir o log de ID ' . $id  . '.',
          'Msg' => 'Verifique o ID passado e novamente!'
      ], 404);
    }
  }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //$this->storeOrUpdate($request, $id);

        $data = $request->all();

        try{
            $erro = auth('api')->user()->erro()->findOrFail($id);
            $erro->update($data);

            return response()->json([
                'data' => [
                    'msg' => 'Log atualizado com sucesso!'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'Erro' => 'Erro ao atualizar: Log não existe ou pertence a outro usuário!',
                'Msg' => 'Verifique os dados e tente novamente!'
            ], 400);
        }
        
    }

    public function destroy($id)
    {
        try{
            $erro = auth('api')->user()->erro()->findOrFail($id);
            $erro->delete();

            return response()->json([
                'data' => [
                    'msg' => 'Log removido com sucesso!'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'Erro' => 'Log não encontrado ou pertence a outro usuário!'
            ], 400);
        }
    }
}
