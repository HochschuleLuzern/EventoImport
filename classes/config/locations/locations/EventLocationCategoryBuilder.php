<?php declare(strict_type=1);

namespace EventoImport\config\locations;

class EventLocationCategoryBuilder
{
    public function __construct()
    {
    }


    public function createNewLocationObjectAndReturnRefId(int $parent_ref_id, string $category_title) : int
    {
        /*
         * The code below are some lines taken from ilObjectGUI and ilObjCategoryGUI which are used to create a new
         * cateogry object. This is because at the writing of this code, there is no ILIAS-Object-Factory class in the
         * ILIAS-Core. Or at least not something that I'm aware of.
         */

        // Create new Category object
        $new_category = new \ilObjCategory();
        $new_category->setType('cat');
        $new_category->setTitle($category_title);
        $new_category->setDescription('');
        $new_category->create();

        // Put in repository tree
        $new_category->createReference();
        $new_category->putInTree($parent_ref_id);
        $new_category->setPermissions($parent_ref_id);

        // default: sort by title
        $settings = new \ilContainerSortingSettings($new_category->getId());
        $settings->setSortMode(\ilContainer::SORT_TITLE);
        $settings->save();

        try {
            // inherit parents content style, if not individual
            $parent_id = \ilObject::_lookupObjId($parent_ref_id);
            $style_id = \ilObjStyleSheet::lookupObjectStyle($parent_id);
            if ($style_id > 0) {
                if (\ilObjStyleSheet::_lookupStandard($style_id)) {
                    \ilObjStyleSheet::writeStyleUsage($new_category->getId(), $style_id);
                }
            }
        } catch (\Exception $e) {
        }

        return (int) $new_category->getRefId();
    }
}
