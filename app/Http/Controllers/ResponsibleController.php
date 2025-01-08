<?php

namespace App\Http\Controllers;

use App\Models\Responsible;
use Illuminate\Http\Request;

class ResponsibleController extends Controller
{
    public static function getList()
    {
        return Responsible::all()->toJson();
    }

    public function edit(Request $request) {
        $resps = [];
        foreach (Responsible::all(['responsible_id', 'title', 'position']) as $r) {
            $resps[$r->responsible_id] = $r;
        }
        $data = $request->post();
        foreach($data as $r) {
            if ($r['responsible_id']) {
                // Edit
                $resps[$r['responsible_id']]['position'] = $r['position'];
                $resps[$r['responsible_id']]['title'] = $r['title'];
                $resps[$r['responsible_id']]->save();
            } else {
                // New
                $n = new Responsible;
                $n->title = $r['title'];
                $n->position = $r['position'];
                $n->save();
            }
            unset($resps[$r['responsible_id']]);
        };

        var_dump($resps);

        if (!empty($data)) {
            Responsible::destroy(array_map(function ($i) {
                return $i->responsible_id;
            }, $resps));
        }
    }
}
