<?php
namespace Robinson\Frontend\Model;
class Category extends \Phalcon\Mvc\Model
{
    const STATUS_INVISIBLE = 0;
    const STATUS_VISIBLE = 1;

    protected static $statusMessages = array
    (
        self::STATUS_INVISIBLE => 'nevidljiv',
        self::STATUS_VISIBLE => 'vidljiv',
    );

    protected $categoryId;

    protected $category;

    protected $description;

    protected $status;

    protected $createdAt;

    protected $updatedAt;

    /**
     * Initializaton method.
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('categories');
        $this->hasMany('categoryId', 'Robinson\Frontend\Model\Images\Category', 'categoryId', array
        (
            'alias' => 'images',
        ));

        $this->hasMany('categoryId', 'Robinson\Frontend\Model\Destination', 'categoryId', array
        (
            'alias' => 'destinations',
        ));

    }

    /**
     * Getter method for category name.
     *
     * @param bool $escapeHtml flag
     *
     * @return string
     */
    public function getCategory($escapeHtml = true)
    {
        return $this->getDI()->getShared('escaper')->escapeHtml($this->category);
    }

    public function getUri()
    {
        $filter = new \Robinson\Frontend\Filter\Unaccent();
        return '/' . $this->getDI()->getShared('tag')->friendlyTitle($filter->filter($this->category)) . '/' . $this->categoryId;
    }

    /**
     * Gets category description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Gets category id.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return (int) $this->categoryId;
    }

    /**
     * Gets related images.
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public function getImages()
    {
        return $this->getRelated('images', array
        (
            'order' => 'sort ASC',
        ));
    }

    /**
     * Gets related destinations.
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public function getDestinations()
    {
        return $this->getRelated('destinations', array
        (
            'status = ' . \Robinson\Frontend\Model\Destination::STATUS_VISIBLE,
            'order' => 'destination ASC',
        ));
    }
}