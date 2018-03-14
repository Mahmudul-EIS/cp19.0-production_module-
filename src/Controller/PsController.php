<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Ps Controller
 *
 * @property \App\Model\Table\PsTable $Ps
 *
 * @method \App\Model\Entity\Ps[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class PsController extends AppController
{

    public $paginate = [
        // Other keys here.
        'maxLimit' => 10
    ];

    public function initialize()
    {
        parent::initialize();
        $this->viewBuilder()->setLayout('mainframe');// TODO: Change the autogenerated stub
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $this->loadModel('PsMonthly');
        if($this->Auth->user('role') == 'verifier'){
            $ps = $this->paginate($this->PsMonthly->find('all')
                ->where(['status' => 'requested']));
        }elseif($this->Auth->user('role') == 'approve-1'){
            $ps = $this->paginate($this->PsMonthly->find('all')
                ->where(['status' => 'verified']));
        }elseif($this->Auth->user('role') == 'approve-2'){
            $ps = $this->paginate($this->PsMonthly->find('all')
                ->where(['status' => 'approval-1']));
        }else{
            $ps = $this->paginate($this->PsMonthly->find('all')
                ->where(['status' => 'requested', 'status' => 'rejected']));
        }
        if($this->Auth->user('role') == 'approve-3' || $this->Auth->user('role') == 'approve-4'){
            $this->loadModel('SerialNumber');
            $this->redirect(array("controller" => "SerialNumber", "action" => "dashboard"));
        }

        $this->set('ps', $ps);
    }

    /**
     * View method
     *
     * @param string|null $id P id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->loadModel('PsMonthly');
        $ps = $this->PsMonthly->get($id, [
            'contain' => []
        ]);

        $reqData = [
            'month' => $ps->month,
            'year' => $ps->year
        ];
        $urlToSales = 'http://salesmodule.acumenits.com/api/month-data';

        $optionsForSales = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => $reqData
            ]
        ];
        $contextForSales  = stream_context_create($optionsForSales);
        $resultFromSales = file_get_contents($urlToSales, false, $contextForSales);
        if ($resultFromSales === FALSE) {
            $this->Flash->error(__('No data found for the selected month. Please try again!'));
        }
        $dataFromSales = json_decode($resultFromSales);
        $this->loadModel('Fgtt');
        foreach($dataFromSales as $sn_match){
            $fgtts = $this->Fgtt->find('all')
                ->where(['so_no' => $sn_match->salesorder_no]);
            foreach($fgtts as $fgtt){
                $sn_match->fgtt = $fgtt;
            }
        }

        $this->set('ps', $ps);
        $this->set('sales', $dataFromSales);
        $this->set('pic', $this->Auth->user('username'));
        $this->set('pic_name', $this->Auth->user('name'));
        $this->set('pic_dept', $this->Auth->user('dept'));
        $this->set('pic_section', $this->Auth->user('section'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $ps = $this->Ps->newEntity();
        if ($this->request->is('post')) {
            $ps = $this->Ps->patchEntity($ps, $this->request->getData());
            if ($this->Ps->save($ps)) {
                $this->Flash->success(__('The p has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The p could not be saved. Please, try again.'));
        }
        $this->set(compact('ps'));
    }

    /**
     * Edit method
     *
     * @param string|null $id P id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->loadModel('PsMonthly');
        $ps = $this->PsMonthly->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $ps = $this->PsMonthly->patchEntity($ps, $this->request->getData());
            if ($this->PsMonthly->save($ps)) {
                $this->Flash->success(__('The ps has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ps could not be saved. Please, try again.'));
        }
        $this->set(compact('ps'));
    }

    /**
     * Delete method
     *
     * @param string|null $id P id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $ps = $this->Ps->get($id);
        if ($this->Ps->delete($ps)) {
            $this->Flash->success(__('The p has been deleted.'));
        } else {
            $this->Flash->error(__('The p could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function main(){
        $urlToSales = 'http://salesmodule.acumenits.com/api/all-data';

        $optionsForSales = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'GET'
            ]
        ];
        $contextForSales  = stream_context_create($optionsForSales);
        $resultFromSales = file_get_contents($urlToSales, false, $contextForSales);
        if ($resultFromSales === FALSE) {
            echo 'ERROR!!';
        }
        $dataFromSales = json_decode($resultFromSales);
        $this->loadModel('SerialNumber');
        $this->loadModel('Fgtt');
        $this->loadModel('PsScheduler');
        foreach($dataFromSales as $sn_match){
            $matches = $this->SerialNumber->find('all')
                ->where(['so_no' => $sn_match->salesorder_no]);
            foreach($matches as $match){
                $sn_match->production_sn = $match;
            }
            foreach($sn_match->soi as $item){
                $schedulerCheck = $this->PsScheduler->find('all')
                    ->where(['so_item_id' => $item->id]);
                $count = 0;
                foreach($schedulerCheck as $checker){
                    $count++;
                    $obName = 'actual'.($count-1);
                    $item->{$obName} = $checker->actual_plan;
                }
                if($count > 0){
                    $item->action = 'edit';
                }else{
                    $item->action = 'add';
                }
            }
            $fgtts = $this->Fgtt->find('all')
                ->where(['so_no' => $sn_match->salesorder_no]);
            foreach($fgtts as $fgtt){
                $sn_match->fgtt = $fgtt;
            }
            $start    = (new \DateTime($sn_match->date))->modify('first day of this month');
            $end      = (new \DateTime($sn_match->delivery_date))->modify('first day of next month');
            $interval = \DateInterval::createFromDateString('1 month');
            $period   = new \DatePeriod($start, $interval, $end);

            $months = array();
            foreach ($period as $dt) {
                $months[] = $dt->format("Y-M");
            }
            $sn_match->months = $months;
        }
        $this->set('sales',$dataFromSales);
        if($this->request->is('post')){
            if($this->request->getData('action') == 'add'){
                for($i = 0; $i < $this->request->getData('total'); $i++){
                    $ps = $this->PsScheduler->newEntity();
                    $monthName = $this->request->getData('month-year-'.$i);
                    $ps->month_year = $monthName;
                    $ps->so_item_id = $this->request->getData('item-id');
                    if($this->request->getData($monthName) > $this->request->getData('plan')){
                        $ps->actual_plan = (int) $this->request->getData('plan');
                    }else{
                        $ps->actual_plan = $this->request->getData($monthName);
                    }
                    $this->PsScheduler->save($ps);
                }
            }else{
                for($i = 0; $i < $this->request->getData('total'); $i++){
                    $monthName = $this->request->getData('month-year-'.$i);
                    $ps_id = $this->PsScheduler->find('all')
                        ->where(['so_item_id' => $this->request->getData('item-id')])
                        ->where(['month_year' => $monthName]);
                    $id = null;
                    foreach($ps_id as $find){
                        $id = $find->id;
                    }
                    $ps = $this->PsScheduler->get($id, [
                        'contain' => []
                    ]);
                    if($this->request->getData($monthName) > $this->request->getData('plan')){
                        $ps->actual_plan = (int) $this->request->getData('plan');
                    }else{
                        $ps->actual_plan = $this->request->getData($monthName);
                    }
                    $this->PsScheduler->save($ps);
                }
            }
            $this->Flash->success(__('The PS Scheduler has been saved.'));

            return $this->redirect(['action' => 'main']);
        }
    }
    public function scheduler(){

    }
    public function dailyReport(){
        $date = $this->request->getQuery('date');
        $this->loadModel('Dr');
        $urlToSales = 'http://salesmodule.acumenits.com/api/all-data';
        $optionsForSales = [
        'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'GET'
            ]
        ];
        $contextForSales  = stream_context_create($optionsForSales);
        $resultFromSales = file_get_contents($urlToSales, false, $contextForSales);
        if ($resultFromSales === FALSE) {
            echo 'ERROR!!';
        }
        $ps = $this->Ps->newEntity();
        $dataFromSales = json_decode($resultFromSales);
        $ps_data = $this->Ps->find('all')
            ->Where(['date'=>$date]);
        $count = 0;
        foreach ($ps_data as $dt){
            $count++;
        }
        if($count > 0){
            $ps_data->action = 'edit';
        }else{
            $ps_data->action = 'add';
        }
        foreach ($dataFromSales as $dts){
            if($count > 0){
                $dts->action = 'edit';
                foreach ($dts->soi as $items){
                    $dr = $this->Dr->find('all')
                        ->Where(['item_no'=>$items->id]);
                    foreach ($dr as $dq){
                        $items->dr_quantity = $dq->quantity;
                        $items->dr_id = $dq->id;
                    }
                }
            }else{
                $dts->action = 'add';
            }
        }
        if ($this->request->is(['post'])) {
            if($this->request->getData('action') == 'add'){
                $ps->date = $this->request->getData('date');
                $ps->total = $this->request->getData('total');
                $ps->created_by = $this->Auth->user('username');
                $ps->status = 'requested';
                if ($this->Ps->save($ps)) {
                    $ps_no = $this->Ps->find('all', ['fields' => 'id'])->last();
                    if($this->request->getData('count') != null){
                        $dr = TableRegistry::get('dr');
                        $drData = array();
                        for($i = 0; $i <= $this->request->getData('count'); $i++){
                            $drData[$i]['ps_id'] = $ps_no['id'];
                            $drData[$i]['so_no'] = $this->request->getData('so_no'.$i);
                            $drData[$i]['item_no'] = $this->request->getData('item_no'.$i);
                            $drData[$i]['quantity'] = $this->request->getData('quantity'.$i);
                        }
                        $pss = $dr->newEntities($drData);
                        foreach($pss as $ps){
                            $dr->save($ps);
                        }
                    }
                    $this->Flash->success(__('The PS daily scheduler has been saved.'));

                    return $this->redirect(['action' => 'scheduler']);
                }
                $this->Flash->error(__('The ps could not be saved. Please, try again.'));
            }else{
                for($i = 0; $i <= $this->request->getData('count'); $i++){
                    $ps_item = $this->Dr->get($this->request->getData('dr_id'.$i), [
                        'contain' => []
                    ]);
                    $ps_item->quantity = $this->request->getData('quantity'.$i);
                    $this->Dr->save($ps_item);
                }
                $this->Flash->success(__('The PS daily scheduler has been saved.'));

                return $this->redirect(['action' => 'scheduler']);
            }
        }
        $this->set('ps_data', $ps_data);
        $this->set('sales',$dataFromSales);
        $this->set('date',$date);
        $this->set('pic', $this->Auth->user('username'));
        $this->set('pic_name', $this->Auth->user('name'));
        $this->set('pic_dept', $this->Auth->user('dept'));
        $this->set('pic_section', $this->Auth->user('section'));
    }

    public function monthlyScheduler(){
        $this->loadModel('PsMonthly');
        $count = $this->PsMonthly->find('all')->last();
        $month = $this->request->getQuery('month');
        $year = $this->request->getQuery('year');
        if($month == null){
            $month = date('m');
        }
        if($year == null){
            $year = date('Y');
        }
        $urlToSales = 'http://salesmodule.acumenits.com/api/month-data';

        $optionsForSales = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => $this->request->getData()
            ]
        ];
        $contextForSales  = stream_context_create($optionsForSales);
        $resultFromSales = file_get_contents($urlToSales, false, $contextForSales);
        if ($resultFromSales === FALSE) {
            $this->Flash->error(__('No data found for the selected month. Please try again!'));
        }
        $dataFromSales = json_decode($resultFromSales);
        $this->loadModel('Fgtt');
        $this->loadModel('PsScheduler');
        foreach($dataFromSales as $sn_match){
            $fgtts = $this->Fgtt->find('all')
                ->where(['so_no' => $sn_match->salesorder_no]);
            foreach($fgtts as $fgtt){
                $sn_match->fgtt = $fgtt;
            }
            foreach ($sn_match->soi as $items){
                $dateObj   = \DateTime::createFromFormat('!m', $month);
                $qty = $this->PsScheduler->find('all')
                    ->where(['so_item_id' => $items->id])
                    ->where(['month_year' => $year.'-'.$dateObj->format('M')]);
                $check = 0;
                foreach ($qty as $q){
                    $check++;
                    if($check > 0){
                        $items->quantity = $q->actual_plan;
                        $items->exist = 'yes';
                    }else{
                        $items->exist = 'no';
                    }
                }
            }
        }
        if($this->request->is('post')){
            if($this->request->getData('total_items') < 1){
                $this->Flash->error(__('No data found for the selected month. Please try again!'));
                return $this->redirect(['action' => 'monthlyScheduler']);
            }
            $ps = $this->PsMonthly->newEntity();
            $ps = $this->PsMonthly->patchEntity($ps, $this->request->getData());
            if ($this->PsMonthly->save($ps)) {
                $this->Flash->success(__('The ps has been saved.'));

                return $this->redirect(['action' => 'monthlyScheduler']);
            }
            $this->Flash->error(__('The ps could not be saved. Please, try again.'));
        }
        $this->set('sales', $dataFromSales);
        $this->set('month', $month);
        $this->set('year', $year);
        $this->set('pic', $this->Auth->user('username'));
        $this->set('pic_name', $this->Auth->user('name'));
        $this->set('pic_dept', $this->Auth->user('dept'));
        $this->set('pic_section', $this->Auth->user('section'));
        $this->set('sn_no', (isset($count->id) ? ($count->id + 1) : 1));
    }

    public function approvalStatus(){
        $this->loadModel('PsMonthly');
        $this->loadModel('Fgtt');
        $ps = $this->paginate($this->PsMonthly->find('all'));
        $dataFromSales = new \stdClass();
        foreach($ps as $p){
            $reqData = [
                'year' => $p->year,
                'month' => $p->month
            ];
            $urlToSales = 'http://salesmodule.acumenits.com/api/month-data';

            $optionsForSales = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => $reqData
                ]
            ];
            $contextForSales  = stream_context_create($optionsForSales);
            $resultFromSales = file_get_contents($urlToSales, false, $contextForSales);
            if ($resultFromSales === FALSE) {
                $this->Flash->error(__('No data found for the selected month. Please try again!'));
            }
            $dataFromSales->{$p->id} = json_decode($resultFromSales);
            foreach($dataFromSales->{$p->id} as $sn_match){
                $fgtts = $this->Fgtt->find('all')
                    ->where(['so_no' => $sn_match->salesorder_no]);
                foreach($fgtts as $fgtt){
                    $sn_match->fgtt = $fgtt;
                }
            }
        }
        $this->set(compact('ps'));
        $this->set('sales', $dataFromSales);
    }

    public function report(){
        $this->loadModel('PsMonthly');
        $this->loadModel('Fgtt');
        $ps = $this->paginate($this->PsMonthly->find('all')
            ->where(['status' => 'approve_2']));
        $dataFromSales = new \stdClass();
        foreach($ps as $p){
            $reqData = [
                'year' => date('Y'),
                'month' => date('m')
            ];
            $urlToSales = 'http://salesmodule.acumenits.com/api/month-data';

            $optionsForSales = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => $reqData
                ]
            ];
            $contextForSales  = stream_context_create($optionsForSales);
            $resultFromSales = file_get_contents($urlToSales, false, $contextForSales);
            if ($resultFromSales === FALSE) {
                $this->Flash->error(__('No data found for the selected month. Please try again!'));
            }
            $dataFromSales->{$p->id} = json_decode($resultFromSales);
            foreach($dataFromSales->{$p->id} as $sn_match){
                $fgtts = $this->Fgtt->find('all')
                    ->where(['so_no' => $sn_match->salesorder_no]);
                foreach($fgtts as $fgtt){
                    $sn_match->fgtt = $fgtt;
                }
            }
        }
        $this->set(compact('ps'));
        $this->set('sales', $dataFromSales);
    }

    public function progressReport(){}

    public function isAuthorized($user){
        // All registered users can add articles
        if ($this->request->getParam('action') === 'main' || $this->request->getParam('action') === 'report' || $this->request->getParam('action') === 'approvalStatus' || $this->request->getParam('action') === 'progressReport') {
            return true;
        }

        if(isset($user['role']) && $user['role'] === 'requester'){
            if(in_array($this->request->action, ['scheduler', 'dailyReport', 'monthlyScheduler'])){
                return true;
            }
        }

        if(isset($user['role']) && $user['role'] === 'verifier'){
            if(in_array($this->request->action, ['edit', 'index', 'view', 'edit'])){
                return true;
            }
        }

        if(isset($user['role']) && $user['role'] === 'approve-1'){
            if(in_array($this->request->action, ['edit', 'index', 'view', 'edit'])){
                return true;
            }
        }

        if(isset($user['role']) && $user['role'] === 'approve-2'){
            if(in_array($this->request->action, ['edit', 'index', 'view', 'edit'])){
                return true;
            }
        }

        return parent::isAuthorized($user);

    }

}
