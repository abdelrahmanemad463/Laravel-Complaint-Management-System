<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Branch, Service, ComplaintSource, ComplaintCategory, ComplaintType, Priority, ComplaintStatus};
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['name'=>'Branch A','code'=>'A'],['name'=>'Branch B','code'=>'B'],['name'=>'Branch C','code'=>'C']] as $row) Branch::updateOrCreate(['code'=>$row['code']], $row + ['is_active'=>true]);
        foreach ([['Take Away','#2563eb'],['Dine In','#7c3aed'],['Delivery','#0891b2']] as [$name,$color]) Service::updateOrCreate(['name'=>$name], ['color'=>$color,'is_active'=>true]);
        foreach (['WhatsApp','Phone','Facebook','In Person','Website','Email'] as $i => $name) ComplaintSource::updateOrCreate(['name'=>$name], ['color'=>'#0f766e','sort_order'=>$i,'is_active'=>true]);
        foreach (['Food Quality','Customer Service','Staff','Delivery','Payment','Order'] as $i => $name) ComplaintCategory::updateOrCreate(['name'=>$name], ['color'=>'#475569','sort_order'=>$i,'is_active'=>true]);
        foreach (['Wrong Order','Missing Item','Late Delivery','Bad Treatment','Wrong Price','Food Quality'] as $i => $name) ComplaintType::updateOrCreate(['name'=>$name], ['color'=>'#64748b','sort_order'=>$i,'is_active'=>true]);
        foreach ([['Low','#16a34a',1],['Medium','#ca8a04',2],['High','#ea580c',3],['Critical','#dc2626',4]] as [$name,$color,$level]) Priority::updateOrCreate(['name'=>$name], ['color'=>$color,'level'=>$level,'sort_order'=>$level,'is_active'=>true]);
        foreach ([['Pending','#64748b'],['In Progress','#2563eb'],['Solved','#16a34a'],['Closed','#0f172a'],['Reopened','#9333ea']] as $i => [$name,$color]) ComplaintStatus::updateOrCreate(['name'=>$name], ['color'=>$color,'sort_order'=>$i,'is_active'=>true]);
    }
}
