<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Branch, Service, ComplaintSource, ComplaintCategory, ComplaintType, Priority, ComplaintStatus};
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name_en'=>'Branch A','name_ar'=>'فرع أ','code'=>'A'],
            ['name_en'=>'Branch B','name_ar'=>'فرع ب','code'=>'B'],
            ['name_en'=>'Branch C','name_ar'=>'فرع ج','code'=>'C'],
        ] as $row) Branch::updateOrCreate(['code'=>$row['code']], $row + ['is_active'=>true, 'sort_order'=>array_search($row['code'], ['A','B','C'], true)]);
        foreach ([
            ['Take Away','وجبة جاهزة','#2563eb'],
            ['Dine In','تناول داخل الفرع','#7c3aed'],
            ['Delivery','توصيل','#0891b2'],
        ] as $i => [$nameEn,$nameAr,$color]) Service::updateOrCreate(['name_en'=>$nameEn], ['name_ar'=>$nameAr,'color'=>$color,'sort_order'=>$i,'is_active'=>true]);
        foreach ([
            ['WhatsApp','واتساب'],['Phone','هاتف'],['Facebook','فيسبوك'],['In Person','بشكل شخصي'],['Website','الموقع الإلكتروني'],['Email','بريد إلكتروني'],
        ] as $i => [$nameEn,$nameAr]) ComplaintSource::updateOrCreate(['name_en'=>$nameEn], ['name_ar'=>$nameAr,'color'=>'#0f766e','sort_order'=>$i,'is_active'=>true]);
        foreach ([
            ['Food Quality','جودة الطعام'],['Customer Service','خدمة العملاء'],['Staff','الموظفون'],['Delivery','التوصيل'],['Payment','الدفع'],['Order','الطلب'],
        ] as $i => [$nameEn,$nameAr]) ComplaintCategory::updateOrCreate(['name_en'=>$nameEn], ['name_ar'=>$nameAr,'color'=>'#475569','sort_order'=>$i,'is_active'=>true]);
        foreach ([
            ['Wrong Order','طلب خاطئ'],['Missing Item','عنصر ناقص'],['Late Delivery','توصيل متأخر'],['Bad Treatment','معاملة سيئة'],['Wrong Price','سعر خاطئ'],['Food Quality','جودة الطعام'],
        ] as $i => [$nameEn,$nameAr]) ComplaintType::updateOrCreate(['name_en'=>$nameEn], ['name_ar'=>$nameAr,'color'=>'#64748b','sort_order'=>$i,'is_active'=>true]);
        foreach ([
            ['Low','منخفضة','#16a34a',1],['Medium','متوسطة','#ca8a04',2],['High','عالية','#ea580c',3],['Critical','حرجة','#dc2626',4],
        ] as [$nameEn,$nameAr,$color,$level]) Priority::updateOrCreate(['name_en'=>$nameEn], ['name_ar'=>$nameAr,'color'=>$color,'level'=>$level,'sort_order'=>$level,'is_active'=>true]);
        foreach ([
            ['Wrong Order','Order','High'],['Missing Item','Order','Medium'],['Late Delivery','Delivery','Medium'],['Bad Treatment','Customer Service','Medium'],['Wrong Price','Payment','High'],['Food Quality','Food Quality','Medium'],
        ] as [$type,$category,$priority]) {
            $record = ComplaintType::where('name_en',$type)->first();
            $categoryRow = ComplaintCategory::where('name_en',$category)->first();
            $priorityRow = Priority::where('name_en',$priority)->first();
            if ($record && $categoryRow && $priorityRow) $record->update(['category_id'=>$categoryRow->id,'priority_id'=>$priorityRow->id]);
        }
        foreach ([
            ['Pending','قيد الانتظار','#64748b'],['In Progress','قيد المعالجة','#2563eb'],['Solved','تم الحل','#16a34a'],['Closed','مغلقة','#0f172a'],['Reopened','أعيد فتحها','#9333ea'],
        ] as $i => [$nameEn,$nameAr,$color]) ComplaintStatus::updateOrCreate(['name_en'=>$nameEn], ['name_ar'=>$nameAr,'color'=>$color,'sort_order'=>$i,'is_active'=>true]);
    }
}