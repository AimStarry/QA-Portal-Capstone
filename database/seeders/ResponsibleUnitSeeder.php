<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\College;
use App\Models\Unit;
use App\Models\User;
use App\Models\ResponsibleUnit;
use App\Models\Laboratory;

class ResponsibleUnitSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Populate responsible_units from colleges
        $colleges = College::all();
        foreach ($colleges as $c) {
            ResponsibleUnit::updateOrCreate(
                ['name' => $c->name],
                [
                    'code' => $c->code,
                    'college_id' => $c->college_id,
                ]
            );
        }

        // 2. Populate responsible_units from units
        $units = Unit::all();
        foreach ($units as $u) {
            ResponsibleUnit::updateOrCreate(
                ['name' => $u->name],
                [
                    'code' => $u->code,
                    'unit_id' => $u->unit_id,
                ]
            );
        }

        // 3. Link existing users to their responsible_unit
        $users = User::all();
        foreach ($users as $user) {
            if ($user->college_id) {
                $ru = ResponsibleUnit::where('college_id', $user->college_id)->first();
                if ($ru) {
                    $user->update(['responsible_unit_id' => $ru->responsible_unit_id]);
                }
            } elseif ($user->unit_id) {
                $ru = ResponsibleUnit::where('unit_id', $user->unit_id)->first();
                if ($ru) {
                    $user->update(['responsible_unit_id' => $ru->responsible_unit_id]);
                }
            }
        }

        // 4. Create some default laboratories under units
        // E.g. Under SOC (School of Computing)
        $socRu = ResponsibleUnit::where('code', 'SOC')->first();
        if ($socRu) {
            Laboratory::firstOrCreate([
                'name' => 'Ada Lovelace Computer Laboratory',
                'responsible_unit_id' => $socRu->responsible_unit_id,
            ]);
            Laboratory::firstOrCreate([
                'name' => 'Alan Turing Network Laboratory',
                'responsible_unit_id' => $socRu->responsible_unit_id,
            ]);
        }

        // Under SNAMS
        $snamsRu = ResponsibleUnit::where('code', 'SNAMS')->first();
        if ($snamsRu) {
            Laboratory::firstOrCreate([
                'name' => 'Nursing Simulation Laboratory',
                'responsible_unit_id' => $snamsRu->responsible_unit_id,
            ]);
        }
    }
}
