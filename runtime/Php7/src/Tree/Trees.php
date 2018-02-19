<?php
/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace Antlr\V4\Runtime\Tree;
use Antlr\V4\Runtime\Atn\Atn;
use Antlr\V4\Runtime\Misc\Utils;
use Antlr\V4\Runtime\Parser;
use Antlr\V4\Runtime\ParserRuleContext;
use Antlr\V4\Runtime\RuleContextInterface;
use Antlr\V4\Runtime\TokenInterface;


/** A set of utility routines useful for all kinds of ANTLR trees. */
class Trees {
    /** Print out a whole tree in LISP form. {@link #getNodeText} is used on the
     *  node payloads to get the text for the nodes.  Detect
     *  parse trees and extract data appropriately.
     */
    public static function toStringTree(TreeInterface $t): string
    {
        return self::toStringTreeByRuleNames($t);
    }

	/** Print out a whole tree in LISP form. {@link #getNodeText} is used on the
     *  node payloads to get the text for the nodes.  Detect
     *  parse trees and extract data appropriately.
     */
	public static function toStringTreeByParser(TreeInterface $t, Parser $recog) {
        $ruleNames = $recog != null ? $recog->getRuleNames() : null;
		$ruleNamesList = $ruleNames != null ? $ruleNames : null;
		return self::toStringTreeByRuleNames($t, $ruleNamesList);
	}

	/** Print out a whole tree in LISP form. {@link #getNodeText} is used on the
     *  node payloads to get the text for the nodes.
     */
	public static function toStringTreeByRuleNames(TreeInterface $t, array $ruleNames = null): string
    {
        $s = Utils::escapeWhitespace(self::getNodeText($t, $ruleNames), false);
		if ( $t->getChildCount() == 0 ) return $s;
		$buf = '';
		$buf .= "(";
		$s = Utils::escapeWhitespace(self::getNodeText($t, $ruleNames), false);
		$buf .= $s;
		$buf .= ' ';
		for ($i = 0; $i < $t->getChildCount(); $i++) {
            if ( $i > 0 ) $buf .= ' ';
            $buf .= self::toStringTreeByRuleNames($t->getChild($i), $ruleNames);
        }
		$buf .= ")";
		return $buf;
	}

	public static function getNodeTextByParser(TreeInterface $t, Parser $recog): string
    {
        $ruleNames = $recog != null ? $recog->getRuleNames() : null;
		$ruleNamesList = $ruleNames != null ? $ruleNames : null;
		return self::getNodeText($t, $ruleNamesList);
	}

	public static function getNodeTextByRuleNames(TreeInterface $t, array $ruleNames = null)
    {
        if ( $ruleNames!=null ) {
            if ( $t instanceof RuleContextInterface ) {
                $ruleIndex = $t->getRuleContext()->getRuleIndex();
				$ruleName = $ruleNames[$ruleIndex];
				$altNumber = $t->getAltNumber();
				if ( $altNumber != Atn::INVALID_ALT_NUMBER ) {
                    return $ruleName . ":" . $altNumber;
                }
				return $ruleName;
			}
            else if ( $t instanceof ErrorNode) {
                return $t->toString();
            }
            else if ( $t instanceof TerminalNode) {
                $symbol = $t->getSymbol();
				if ($symbol != null) {
                    $s = $symbol->getText();
					return $s;
				}
			}
        }

        // no recog for rule names
        $payload = $t->getPayload();
		if ( $payload instanceof TokenInterface ) {
            return $payload->getText();
		}
		return $t->getPayload()->toString();
	}

	/**
     * Return ordered list of all children of this node
     *
     * @return TreeInterface[]
     */
	public static function getChildren(TreeInterface $t): array
    {
        $kids = [];
		for ($i = 0; $i < $t->getChildCount(); $i++) {
            $kids[] = $t->getChild($i);
        }

		return $kids;
	}

	/** Return a list of all ancestors of this node.  The first node of
     *  list is the root and the last is the parent of this node.
     *
     *  @return TreeInterface[]
     *  @since 4.5.1
     */
	public static function getAncestors(TreeInterface $t) {
        if ( $t->getParent() == null ) return [];
        $ancestors = [];
		$t = $t->getParent();
		while ( $t != null ) {
            $ancestors[] = $t; // NOT insert at start
            $t = $t->getParent();
        }
		return array_reverse($ancestors);
	}

	/** Return true if t is u's parent or a node on path to root from u.
     *  Use == not equals().
     *
     *  @since 4.5.1
     */
	public static function isAncestorOf(TreeInterface $t, TreeInterface $u): bool
    {
        if ( $t == null || $u == null || $t->getParent() == null ) return false;
        $p = $u->getParent();
		while ( $p != null ) {
            if ( $t == $p ) return true;
            $p = $p->getParent();
        }
		return false;
	}

    /**
     * @return ParseTreeInterface[]
     */
	public static function findAllTokenNodes(ParseTreeInterface $t, int $ttype)
    {
        return self::findAllNodes($t, $ttype, true);
    }

    /**
     * @return ParseTreeInterface[]
     */
	public static function findAllRuleNodes(ParseTreeInterface $t, int $ruleIndex)
    {
        return self::findAllNodes($t, $ruleIndex, false);
    }

    /**
     * @return ParseTreeInterface[]
     */
	public static function findAllNodes(ParseTreeInterface $t, int $index, boolean $findTokens)
    {
        $nodes = [];
		self::_findAllNodes($t, $index, $findTokens, $nodes);
		return $nodes;
	}

	public static function _findAllNodes(ParseTreeInterface $t, int $index, bool $findTokens, array &$nodes)
	{
        // check this node (the root) first
        if ( $findTokens && $t instanceof TerminalNode ) {
			if ( $t->getSymbol()->getType() == $index ) $nodes[] = $t;
		}
        else if ( !$findTokens && $t instanceof ParserRuleContext ) {
			if ( $t->getRuleIndex() == $index ) $nodes[] = $t;
		}
        // check children
        for ($i = 0; $i < $t->getChildCount(); $i++) {
            self::_findAllNodes($t->getChild($i), $index, $findTokens, $nodes);
        }
	}

	/** Get all descendents; includes t itself.
     *
     * @since 4.5.1
     * @return ParseTreeInterface[]
     */
	public static function getDescendants(ParseTreeInterface $t): array
    {
        $nodes = [];
		$nodes[] = $t;

		$n = $t->getChildCount();
		for ($i = 0 ; $i < $n ; $i++) {
		    foreach (self::getDescendants($t->getChild($i)) as $o) {
		        $nodes[] = $o;
            }
        }
		return $nodes;
	}

	/** Find smallest subtree of t enclosing range startTokenIndex..stopTokenIndex
     *  inclusively using postorder traversal.  Recursive depth-first-search.
     *
     *  @since 4.5.1
     */
	public static function getRootOfSubtreeEnclosingRegion(ParseTreeInterface $t, int $startTokenIndex, int $stopTokenIndex): ParserRuleContext
	{
        $n = $t->getChildCount();
		for ($i = 0; $i < $n; $i++) {
            $child = $t->getChild($i);
			$r = self::getRootOfSubtreeEnclosingRegion($child, $startTokenIndex, $stopTokenIndex);
			if ( $r != null ) return $r;
		}
		if ( $t instanceof ParserRuleContext ) {
            $r = /*(ParserRuleContext)*/ $t;
			if ( $startTokenIndex >= $r->getStart()->getTokenIndex() && // is range fully contained in t?
                ($r->getStop() == null || $stopTokenIndex <= $r->getStop()->getTokenIndex()) )
            {
                // note: r.getStop()==null likely implies that we bailed out of parser and there's nothing to the right
                return $r;
            }
		}
		return null;
	}

	/** Replace any subtree siblings of root that are completely to left
     *  or right of lookahead range with a CommonToken(Token.INVALID_TYPE,"...")
     *  node. The source interval for t is not altered to suit smaller range!
     *
     *  WARNING: destructive to t.
     *
     *  @since 4.5.1
     */
	public static function stripChildrenOutOfRange(ParserRuleContext $t, ParserRuleContext $root, int $startIndex, int $stopIndex): void
	{
        if ( $t == null ) return;
        for ($i = 0; $i < $t->getChildCount(); $i++) {
            $child = $t->getChild($i);
			$range = $child->getSourceInterval();
			if ( $child instanceof ParserRuleContext && ($range->b < $startIndex || $range->a > $stopIndex) ) {
                if ( is_subclass_of($child, $root::class) ) { // replace only if subtree doesn't have displayed root
                    $abbrev = new CommonToken(Token.INVALID_TYPE, "...");
					t.children.set(i, new TerminalNodeImpl(abbrev));
				}
            }
		}
	}

	/** Return first node satisfying the pred
     *
     *  @since 4.5.1
     */
	public static Tree findNodeSuchThat(Tree t, Predicate<Tree> pred) {
    if ( pred.test(t) ) return t;

    if ( t==null ) return null;

    int n = t.getChildCount();
		for (int i = 0 ; i < n ; i++){
        Tree u = findNodeSuchThat(t.getChild(i), pred);
			if ( u!=null ) return u;
		}
		return null;
	}

	private Trees() {
}
}

