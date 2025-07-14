<?php

namespace App\Http\Controllers;

use App\Models\Responsible;
use Illuminate\Http\Request;
use App\Util;
class ResponsibleController extends Controller
{
    public const RESPONSIBLE_NOT_FOUND = "Такого ответственного не существует";
    public const RESPONSIBLE_ALREADY_EXISTS = "Такой сотрудник уже существует";
    public static function get()
    {
        return Response(Responsible::all()->toArray(), 200);
    }

    public static function create(Request $request){
        $exists = Util::checkDublicate(new Responsible(), ['title'], $request->post());
        if($exists){
            return Response(['error' => self::RESPONSIBLE_ALREADY_EXISTS], 400);
        }
        return Response(
            Responsible::create(
                $request->only(
                    (new Responsible())->getFillable()
                )
            ), 201);
    }

    public static function update(Request $request){
        $model = Responsible::where('responsible_id', $request->post('responsible_id'))->get();
        if (!$model) {
            return Response(['error' => self::RESPONSIBLE_NOT_FOUND], 404);
        }
        $model->update($request->post());
        return Response(['message' => [
            'type'  => 'success',
            'title' => 'Данные обновлены'
        ]], 200);
    }

    public static function delete(Request $request){
        $model = Responsible::where('responsible_id', $request->post('responsible_id'))->get();
        if (!$model) {
            return Response(['error' => self::RESPONSIBLE_NOT_FOUND], 404);
        }
        $model->delete();
        return Response([
            'message'   => 'success',
            'title'     => 'Ответственный удалён'
        ], 200);
    }

    public function edit(Request $request) {
        $resps = [];
        foreach (Responsible::all(['responsible_id', 'title', 'position']) as $r) {
            $resps[$r->responsible_id] = $r;
        }
        $data = $request->post();
        foreach($data as $r) {
            if (isset($r['responsible_id'])) {
                // Edit
                $resps[$r['responsible_id']]->position = $r['position'];
                $resps[$r['responsible_id']]->title = $r['title'];
                $resps[$r['responsible_id']]->save();
                unset($resps[$r['responsible_id']]);
            } else {
                // New
                $n = new Responsible;
                $n->title = $r['title'];
                $n->position = $r['position'];
                $n->save();
            }
        }
        if (!empty($data)) {
            Responsible::destroy(array_map(function ($i) {
                return $i->responsible_id;
            }, $resps));
        }
    }
}
