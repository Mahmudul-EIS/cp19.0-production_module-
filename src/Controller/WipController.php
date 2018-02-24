<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Wip Controller
 *
 * @property \App\Model\Table\WipTable $Wip
 *
 * @method \App\Model\Entity\Wip[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class WipController extends AppController
{
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
        $wip = $this->paginate($this->Wip);

        $this->set(compact('wip'));
    }

    /**
     * View method
     *
     * @param string|null $id Wip id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $wip = $this->Wip->get($id, [
            'contain' => ['WipSection']
        ]);

        $this->set('wip', $wip);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->loadModel('SerialNumber');
        $this->loadModel('WipSection');
        $sn = $this->SerialNumber->find('all');
        $sn_id =null;
        foreach($sn as $s){
            $sn_id .= '{label:"'.$s->so_no.'",idx:"'.$s->id.'",idy:"'.$s->model.'",idz:"'.$s->version.'"},';
        }
        $wip = $this->Wip->newEntity();
        if ($this->request->is('post')) {
            $this->autoRender = false;
            $wip = $this->Wip->patchEntity($wip, $this->request->getData());
//            if($this->request->getData('cb_1') == '' || $this->request->getData('cb_2') == '' || $this->request->getData('cb_3') == '' || $this->request->getData('cb_4') == '' || $this->request->getData('cb_5') == '' || $this->request->getData('cb_6') == '' || $this->request->getData('cb_7') == '' || $this->request->getData('cb_8') == '' || $this->request->getData('cb_9') == '') {
//                $this->Flash->error(__('The wp could not be saved. Please, try again.'));
//                return $this->redirect(['action' => 'add']);
//            }
            if ($this->Wip->save($wip)) {
                $wip_no = $this->Wip->find('all', ['fields' => 'id'])->last();
                for($i =1;$i<=9;$i++){
                    if($this->request->getData('cb_'.$i) != ''){
                        $wips = $this->WipSection->newEntity();
                        $wips->wip_id = $wip_no['id'];
                        $wips->operator_name =$this->request->getData('operator_name_'.$i);
                        $wips->supervisor_name =  $this->request->getData('supervisor_name_'.$i);
                        $wips->section = $this->request->getData('section'.$i);
                        $wips->status = 'requested';
                        $this->WipSection->save($wips);
                    }
                }
                $this->Flash->success(__('The wp has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The wp could not be saved. Please, try again.'));
        }
        $count = $this->Wip->find('all')->last();
        $this->set(compact('wip'));
        $this->set('sn_id',$sn_id);
        $this->set('wp_no', (isset($count->id) ? ($count->id + 1) : 1));
        $this->set('pic', $this->Auth->user('username'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Wip id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $wip = $this->Wip->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $wip = $this->Wip->patchEntity($wip, $this->request->getData());
            if ($this->Wip->save($wip)) {
                $this->Flash->success(__('The wip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The wip could not be saved. Please, try again.'));
        }
        $this->set(compact('wip'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Wip id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $wip = $this->Wip->get($id);
        if ($this->Wip->delete($wip)) {
            $this->Flash->success(__('The wip has been deleted.'));
        } else {
            $this->Flash->error(__('The wip could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}