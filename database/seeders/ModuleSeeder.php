<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    public function run()
    {
        Module::create(['name' => 'Login']);
        Module::create(['name' => 'Employee']);
        Module::create(['name' => 'Projects']);
        Module::create(['name' => 'Work Schedule']);
        Module::create(['name' => 'Attendance']);
        Module::create(['name' => 'Overtime']);
        Module::create(['name' => 'Payroll']);
        Module::create(['name' => 'Reports']);
    }
}
