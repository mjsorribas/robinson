<?php
namespace Robinson\Backend\Controllers;

class DestinationController extends \Phalcon\Mvc\Controller
{
    /**
     * Page where detailed list of destinations is displayed.
     *
     * @return void
     */
    public function indexAction()
    {
        $this->view->destinations = array();

        // preset categoryId if one was already set
        if ($this->session->get('categoryId') && !$this->request->hasQuery('categoryId')) {
            $this->tag->setDefault('categoryId', (int) $this->session->get('categoryId'));
            $_GET['categoryId'] = (int) $this->session->get('categoryId');
            $this->session->remove('categoryId');
        }

        if ($this->request->hasQuery('categoryId')) {
            $destinations = $this->getDI()->get('Robinson\Backend\Models\Destination');
            $this->view->destinations = $destinations->find(
                array(
                    'conditions' => 'categoryId = ' . (int) $this->request->getQuery('categoryId'),
                    'order' => 'destinationId DESC',
                )
            );
            $this->tag->setDefault('categoryId', (int) $this->request->getQuery('categoryId'));
            $this->session->set('categoryId', (int) $this->request->getQuery('categoryId'));
        }

        $categories = \Robinson\Backend\Models\Category::find(
            array(
                'order' => 'categoryId DESC',
            )
        );

        $this->view->categories = $categories;
    }

    /**
     * Creates new destination. If successful will redirect to update page of that destination.
     *
     * @return void
     */
    public function createAction()
    {
        if ($this->request->isPost()) {
            /* @var $destination \Robinson\Backend\Models\Destination */
            $destination = $this->getDI()->get('Robinson\Backend\Models\Destination');
            $destination->setCategoryId($this->request->getPost('categoryId'))
                ->setDestination($this->request->getPost('destination'))
                ->setDescription($this->request->getPost('description'))
                ->setStatus($this->request->getPost('status'));

            $destinationTabs = array();
            foreach ($this->request->getPost('tabs') as $tabType => $tabDescription) {
                // not added, pass
                if (!$tabDescription) {
                    continue;
                }

                $destinationTab = new \Robinson\Backend\Models\Tabs\Destination();
                $destinationTab->setType($tabType)
                    ->setTitle($destinationTab->resolveTypeToTitle())
                    ->setDescription($tabDescription);
                $destinationTabs[] = $destinationTab;
            }

            $destination->setTabs($destinationTabs);

            // redirect to update upon successful save
            $destination->create();
            return $this->response->redirect(
                array
                (
                    'for' => 'admin-update',
                    'controller' => 'destination',
                    'action' => 'update',
                    'id' => $destination->getDestinationId(),
                )
            )->send();
        }

        $categories = \Robinson\Backend\Models\Category::find(
            array(
                'order' => 'categoryId DESC',
            )
        );

        $this->view->setVar('categories', $categories);

        $this->view->tabs = $this->getDI()->getShared('config')->application->destination->tabs->toArray();
    }

    /**
     * Updates destination by id. Accepts images.
     *
     * @return void
     */
    public function updateAction()
    {
        set_time_limit(300);
        /* @var $destination \Robinson\Backend\Models\Destination */
        $destination = \Robinson\Backend\Models\Destination::findFirstByDestinationId(
            $this->dispatcher->getParam('id')
        );

        if ($this->request->isPost()) {
            $destination->setCategoryId($this->request->getPost('categoryId'))
                ->setDestination($this->request->getPost('destination'))
                ->setDescription($this->request->getPost('description'))
                ->setStatus($this->request->getPost('status'));

            $destinationTabs = array();
            foreach ($this->request->getPost('tabs') as $tabType => $tabDescription) {
                $tabDescription = trim($tabDescription);
                $tab = $destination->getTabs(
                    array
                    (
                        'type = :type:',
                        'bind' => array(
                            'type' => $tabType,
                        ),
                    )
                )->getFirst();

                // new tab
                if (!$tab && $tabDescription) {
                    $tab = new \Robinson\Backend\Models\Tabs\Destination();
                    $tab->setType($tabType)
                       ->setTitle($tab->resolveTypeToTitle());
                }

                // deleted tab
                if ($tab && !$tabDescription) {
                    $tab->delete();
                    continue;
                }

                // never existed and wasn't entered
                if (!$tab && !$tabDescription) {
                    continue;
                }

                $tab->setDescription($tabDescription);
                $destinationTabs[] = $tab;
            }

            $destination->setTabs($destinationTabs);

            // sort?
            $sort = $this->request->getPost('sort');

            $images = array();

            if ($sort) {
                foreach ($destination->getImages() as $image) {
                    $image->setSort($sort[$image->getImageId()]);
                    $images[] = $image;
                }
            }

            $files = $this->request->getUploadedFiles();
            foreach ($files as $file) {
                /* @var $imageCategory \Robinson\Backend\Models\Images\Destination */
                $destinationImage = $this->getDI()->get('Robinson\Backend\Models\Images\Destination');
                $destinationImage->createFromUploadedFile($file);
                $images[] = $destinationImage;
            }

            $destination->setImages($images);
            $destination->update();
        }

        $categories = \Robinson\Backend\Models\Category::find(
            array(
                'order' => 'categoryId DESC',
            )
        );

        $this->view->categories = $categories;
        $this->view->destination = $destination;
        $this->view->tabs = $this->getDI()->getShared('config')->application->destination->tabs->toArray();

        $tabs = $destination->getTabs();
        foreach ($tabs as $tab) {
            $this->tag->setDefault('tabs[' . $tab->getType() . ']', $tab->getDescription());
        }
        $this->tag->setDefault('status', $destination->getStatus());
        $this->tag->setDefault('categoryId', $destination->getCategoryId());
        $this->tag->setDefault('destination', $destination->getDestination());
        $this->tag->setDefault('description', $destination->getDescription());

    }

    /**
     * Deletes destination image. Outputs JSON.
     *
     * @return string json response
     */
    public function deleteImageAction()
    {
        $image = \Robinson\Backend\Models\Images\Destination::findFirst($this->request->getPost('id'));
        $this->response->setJsonContent(array('response' => $image->delete()))->setContentType('application/json');
        return $this->response;
    }
}
