<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Erro;
use App\User;
use Illuminate\Support\Facades\Auth;


class ErroController extends Controller
{
    public function index(Request $request)
    {/*
        $usuarios = User::all();
        $registros = Erro::all();

        $usuarios = Auth::user();
        $userId  = $usuarios->id;

        $ambiente   = $request->get('ambiente');
        $ordenacao  = $request->get('ordenacao');
        $nivel      = $request->get('nivel');
        $descricao  = $request->get('descricao');
        $origem     = $request->get('origem');


        $registros = Erro::all();
        */
        $usuarios = Auth::user();

        $ambiente   = $request->get('ambiente');
        $ordenacao  = $request->get('order');
        $nivel      = $request->get('nivel');
        $descricao  = $request->get('descricao');
        $origem     = $request->get('origem');
    
        $filters = [
          ['status', '=', 'Ativo']
        ];
    
//        if ($usuarios->admin != 1)
//          array_push($filters, ["usuario_id", '=', $usuarios->id]);

        if ($ambiente !== null)
          array_push($filters, ["ambiente", '=', $ambiente]);      
    
        $order      = 'eventos';
        $direcao    = 'desc';
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
    
    
        $registros = Erro::where($filters)
           ->orderBy($order, $direcao)->get();

        //  $sql = $registros = Erro::where($filters)
        //    ->orderBy($order, 'asc')->toSql();

        //  $sql = $registros = Erro::where($filters)
        //    ->orderBy($order, 'asc')->getBindings();

        //   $usuarios = User::all();
        //   $registros = Erro::all();

      if(auth()->user()) {
        return view('erros.index', compact('registros'), compact('usuarios'));
      }
    }

    public function adicionar()
    {
        return view('erros.adicionar');
    }

    public function salvar(Request $req)
    {

        $dados = $req->all();

        Erro::create($dados);
        return redirect()->route('erros');
    }

    public function arquivar($id)
    {
        $erro = Erro::find($id);
        $erro->status = 'Concluido';
        $erro->update();
        return redirect()->route('erros');
    }

    public function atualizar(Request $req, $id)
    {
        $dados = $req->all();


        Erro::find($id)->update($dados);
            return redirect()->route('erros');
    }

    public function deletar($id) {
        Erro::find($id)->delete();
        return redirect()->route('erros');
    }

    public function detalhes($id)
    {
        $usuarios = User::all();
        $registros = Erro::find($id);

        return view('erros.detalhes', compact('registros'), compact('usuarios'));
    }


}
