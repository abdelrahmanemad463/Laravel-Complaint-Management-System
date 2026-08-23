<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Customer,Complaint,Branch,Service,ComplaintSource,ComplaintCategory,ComplaintType,Priority,ComplaintStatus,User};
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email','admin@example.com')->first(); $branch = Branch::first(); $service = Service::first(); $source = ComplaintSource::first(); $category = ComplaintCategory::first(); $type = ComplaintType::first(); $priority = Priority::where('name','High')->first(); $status = ComplaintStatus::where('name','Pending')->first();
        foreach ([['Ahmed Mohamed','01012345678','01112345678'],['Mona Ali','01212345678',null],['Omar Hassan','01512345678','01099887766']] as [$name,$phone,$phone2]) {
            $customer = Customer::firstOrCreate(['phone_primary'=>$phone], ['name'=>$name,'phone_2'=>$phone2,'address'=>'Main Street']);
            Complaint::firstOrCreate(['customer_id'=>$customer->id,'short_description'=>'Wrong order received'], ['branch_id'=>$branch->id,'service_id'=>$service->id,'source_id'=>$source->id,'category_id'=>$category->id,'type_id'=>$type->id,'priority_id'=>$priority->id,'status_id'=>$status->id,'description'=>'Customer received a different order and requested assistance.','complaint_date'=>now()->toDateString(),'created_by'=>$user->id]);
        }
    }
}
