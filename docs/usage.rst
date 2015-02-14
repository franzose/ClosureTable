.. index::
   single: Usage

Usage
=====

Time of coding
--------------

Once your models and their database tables are created, at last, you can start actually coding. Here I will show you ClosureTable's specific approaches.

Direct ancestor (parent)
------------------------

.. code-block:: php

	$parent = Page::find(15)->getParent();

Ancestors
---------

.. code-block:: php

	$page = Page::find(15);
	$ancestors = $page->getAncestors();
	$ancestors = $page->getAncestorsWhere('position', '=', 1);
	$hasAncestors = $page->hasAncestors();
	$ancestorsNumber = $page->countAncestors();

Direct descendants (children)
-----------------------------

.. code-block:: php

	$page = Page::find(15);
	$children = $page->getChildren();
	$hasChildren = $page->hasChildren();
	$childrenNumber = $page->countChildren();

	$newChild = new Page(array(
		'title' => 'The title',
		'excerpt' => 'The excerpt',
		'content' => 'The content of a child'
	));

	$newChild2 = new Page(array(
		'title' => 'The title',
		'excerpt' => 'The excerpt',
		'content' => 'The content of a child'
	));

	$page->addChild($newChild);

	//you can set child position
	$page->addChild($newChild, 5);

	//you can get the child
	$child = $page->addChild($newChild, null, true);

	$page->addChildren([$newChild, $newChild2]);

	$page->getChildAt(5);
	$page->getFirstChild();
	$page->getLastChild();
	$page->getChildrenRange(0, 2);

	$page->removeChild(0);
	$page->removeChild(0, true); //force delete
	$page->removeChildren(0, 3);
	$page->removeChildren(0, 3, true); //force delete

Descendants
-----------

.. code-block:: php

	$page = Page::find(15);
	$descendants = $page->getDescendants();
	$descendants = $page->getDescendantsWhere('position', '=', 1);
	$descendantsTree = $page->getDescendantsTree();
	$hasDescendants = $page->hasDescendants();
	$descendantsNumber = $page->countDescendants();

Siblings
--------

.. code-block:: php

	$page  = Page::find(15);
	$first = $page->getFirstSibling(); //or $page->getSiblingAt(0);
	$last  = $page->getLastSibling();
	$atpos = $page->getSiblingAt(5);

	$prevOne = $page->getPrevSibling();
	$prevAll = $page->getPrevSiblings();
	$hasPrevs = $page->hasPrevSiblings();
	$prevsNumber = $page->countPrevSiblings();

	$nextOne = $page->getNextSibling();
	$nextAll = $page->getNextSiblings();
	$hasNext = $page->hasNextSiblings();
	$nextNumber = $page->countNextSiblings();

	//in both directions
	$hasSiblings = $page->hasSiblings();
	$siblingsNumber = $page->countSiblings();

	$sibligns = $page->getSiblingsRange(0, 2);

	$page->addSibling(new Page);
	$page->addSibling(new Page, 3); //third position

	//add and get the sibling
	$sibling = $page->addSibling(new Page, null, true);

	$page->addSiblings([new Page, new Page]);
	$page->addSiblings([new Page, new Page], 5); //insert from fifth position

Roots (entities that have no ancestors)
---------------------------------------

.. code-block:: php

	$roots = Page::getRoots();
	$isRoot = Page::find(23)->isRoot();
	Page::find(11)->makeRoot();

Entire tree
-----------

.. code-block:: php

	$tree = Page::getTree();
	$treeByCondition = Page::getTreeWhere('position', '>=', 1);

You deal with the collection, thus you can control its items as you usually do. Descendants? They are already loaded.

.. code-block:: php

	$tree = Page::getTree();
	$page = $tree->find(15);
	$children = $page->getChildren();
	$child = $page->getChildAt(3);
	$grandchildren = $page->getChildAt(3)->getChildren(); //and so on

Moving
------

.. code-block:: php

	$page = Page::find(25);
	$page->moveTo(0, Page::find(14));
	$page->moveTo(0, 14);

Deleting subtree
----------------

If you don't use foreign keys for some reason, you can delete subtree manually. This will delete the page and all its descendants:

.. code-block:: php

	$page = Page::find(34);
	$page->deleteSubtree();
	$page->deleteSubtree(true); //with subtree ancestor
	$page->deleteSubtree(false, true); //without subtree ancestor and force delete

