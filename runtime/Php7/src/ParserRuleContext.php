<?php
/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
namespace Antlr\V4\Runtime;
use Antlr\V4\Runtime\Misc\Interval;
use Antlr\V4\Runtime\Tree\ErrorNode;
use Antlr\V4\Runtime\Tree\ParseTreeInterface;
use Antlr\V4\Runtime\Tree\ParseTreeListener;
use Antlr\V4\Runtime\Tree\TerminalNode;


/** A rule invocation record for parsing.
 *
 *  Contains all of the information about the current rule not stored in the
 *  RuleContext. It handles parse tree children list, Any ATN state
 *  tracing, and the default values available for rule invocations:
 *  start, stop, rule index, current alt number.
 *
 *  Subclasses made for each rule and grammar track the parameters,
 *  return values, locals, and labels specific to that rule. These
 *  are the objects that are returned from rules.
 *
 *  Note text is not an actual field of a rule return value; it is computed
 *  from start and stop using the input stream's toString() method.  I
 *  could add a ctor to this so that we can pass in and store the input
 *  stream, but I'm not sure we want to do that.  It would seem to be undefined
 *  to get the .text property anyway if the rule matches tokens from multiple
 *  input streams.
 *
 *  I do not use getters for fields of objects that are used simply to
 *  group values such as this aggregate.  The getters/setters are there to
 *  satisfy the superclass interface.
 */
class ParserRuleContext extends RuleContextInterface {
	/** If we are debugging or building a parse tree for a visitor,
	 *  we need to track all of the tokens and rule invocations associated
	 *  with this rule's context. This is empty for parsing w/o tree constr.
	 *  operation because we don't the need to track the details about
	 *  how we parse this rule.
     *
     * @var ParseTreeInterface[]
	 */
	public $children = null;

	/** For debugging/tracing purposes, we want to track all of the nodes in
	 *  the ATN traversed by the parser for a particular rule.
	 *  This list indicates the sequence of ATN nodes used to match
	 *  the elements of the children list. This list does not include
	 *  ATN nodes and other rules used to match rule invocations. It
	 *  traces the rule invocation node itself but nothing inside that
	 *  other rule's ATN submachine.
	 *
	 *  There is NOT a one-to-one correspondence between the children and
	 *  states list. There are typically many nodes in the ATN traversed
	 *  for each element in the children list. For example, for a rule
	 *  invocation there is the invoking state and the following state.
	 *
	 *  The parser setState() method updates field s and adds it to this list
	 *  if we are debugging/tracing.
	 *
	 *  This does not trace states visited during prediction.
	 */
//	public List<Integer> states;

    /**
     * @var TokenInterface
     */
	public $start;

    /**
     * @var TokenInterface
     */
    public $stop;

	/**
	 * The exception that forced this rule to return. If the rule successfully
	 * completed, this is {@code null}.
     *
     * @var RecognitionException
	 */
	public $exception;

	/** COPY a ctx (I'm deliberately not using copy constructor) to avoid
	 *  confusion with creating node with parent. Does not copy children
	 *  (except error leaves).
	 *
	 *  This is used in the generated parser code to flip a generic XContext
	 *  node for rule X to a YContext for alt label Y. In that sense, it is
	 *  not really a generic copy function.
	 *
	 *  If we do an error sync() at start of a rule, we might add error nodes
	 *  to the generic XContext so this function must copy those nodes to
	 *  the YContext as well else they are lost!
	 */
	public function copyFrom(ParserRuleContext $ctx): void
    {
		$this->parent = $ctx->parent;
		$this->invokingState = $ctx->invokingState;

		$this->start = $ctx->start;
		$this->stop = $ctx->stop;

		// copy any error nodes to alt label node
		if ( $ctx->children != null ) {
			$this->children = [];
			// reset parent pointer for any error nodes
			foreach ( $ctx->children as $child ) {
				if ( $child instanceof ErrorNode ) {
					$this->addChild($child);
				}
			}
		}
	}

	public function __construct(ParserRuleContext $parent = null, int $invokingStateNumber) {
		parent::__construct($parent, $invokingStateNumber);
	}

	// Double dispatch methods for listeners

	public function enterRule(ParseTreeListener $listener): void
    {
    }

	public function exitRule(ParseTreeListener $listener): void
    {
    }

	/** Add a parse tree node to this as a child.  Works for
	 *  internal and leaf nodes. Does not set parent link;
	 *  other add methods must do that. Other addChild methods
	 *  call this.
	 *
	 *  We cannot set the parent pointer of the incoming node
	 *  because the existing interfaces do not have a setParent()
	 *  method and I don't want to break backward compatibility for this.
	 *
	 *  @since 4.7
	 */
	public function addAnyChild(ParseTreeInterface $t): ParseTreeInterface
    {
		if ( $this->children == null ) $this->children = [];
		$this->children[] = $t;
		return $t;
	}

	public function addChild(ParseTreeInterface $t): ParseTreeInterface {
	    if ($t instanceof TerminalNode) {
	        $t->setParent($this);
        }

        return $this->addAnyChild($t);
	}


	/** Add an error node child and force its parent to be this node.
	 *
	 * @since 4.7
	 */
	public function addErrorNode(ErrorNode $errorNode): ErrorNode
    {
		$errorNode->setParent($this);
		return $this->addAnyChild($errorNode);
	}

//	public function trace(int $s): void {
//		if ( $this->states == null ) $this->states = [];
//		$states->add($s);
//	}

	/** Used by enterOuterAlt to toss out a RuleContext previously added as
	 *  we entered a rule. If we have # label, we will need to remove
	 *  generic ruleContext object.
	 */
	public function removeLastChild(): void
    {
		if ( $this->children!=null ) {
		    array_splice($this->children, children.size()-1, 1);
		}
	}


	/**
     * Override to make type more specific
     *
     * @return ParserRuleContext|RuleContextInterface
     */
	public function getParent()
    {
		return parent::getParent();
	}

	public function getChild(int $i): ParseTreeInterface
    {
		return $this->children != null && $i >= 0 && $i < count($this->children) ? $this->children[$i] : null;
	}

	public function getTypedChild(string $ctxType, int $i): ParseTreeInterface
    {
		if ( $this->children == null || $i < 0 || $i >= count($this->children) ) {
			return null;
		}

		$j = -1; // what element have we found with ctxType?
		foreach ($this->children as $o) {
			if ( $o instanceof $ctxType ) {
				$j++;
				if ( $j == $i ) {
					return $o;
				}
			}
		}
		return null;
	}

	public function getToken(int $ttype, int $i): TerminalNode
    {
		if ( $this->children == null || $i < 0 || $i >= count($this->children) ) {
			return null;
		}

		$j = -1; // what token with ttype have we found?
		foreach ($this->children as $o) {
			if ( $o instanceof TerminalNode ) {
				$tnode = $o;
				$symbol = $tnode->getSymbol();
				if ( $symbol->getType() == $ttype ) {
					$j++;
					if ( $j == $i ) {
						return $tnode;
					}
				}
			}
		}

		return null;
	}

    /**
     * @return TerminalNode[]
     */
	public function getTokens(int $ttype) {
		if ( $this->children == null ) {
			return [];
		}

		$tokens = null;
		foreach ($this->children as $o) {
			if ( $o instanceof TerminalNode ) {
				$tnode = $o;
				$symbol = $tnode->getSymbol();
				if ( $symbol->getType() == $ttype ) {
					if ( $tokens == null ) {
						$tokens = [];
					}
					$tokens[] = $tnode;
				}
			}
		}

		if ( $tokens == null ) {
			return [];
		}

		return $tokens;
	}

    /**
     * @param string $ctxType
     * @param int $i
     * @return ParserRuleContext|ParseTreeInterface
     */
    public function getTypedRuleContext(string $ctxType, int $i)
    {
		return $this->getTypedChild($ctxType, $i);
	}

    /**
     * @param string $ctxType
     * @return RuleContextInterface[]
     */
	public function getRuleContexts(string $ctxType)
    {
		if ( $this->children == null ) {
			return [];
		}

		$contexts = null;
		foreach ($this->children as $o) {
			if ( $o instanceof $ctxType ) {
				if ( $contexts == null ) {
					$contexts = [];
				}

				$contexts[] = $o;
			}
		}

		if ( $contexts == null ) {
			return [];
		}

		return $contexts;
	}

	public function getChildCount(): int
    {
        return $this->children != null ? count($this->children) : 0;
    }

	public function getSourceInterval(): Interval
    {
		if ( $this->start == null ) {
			return new Interval(-1,-2);
		}
		if ( $this->stop == null || $this->stop->getTokenIndex() < $this->start->getTokenIndex() ) {
			return Interval::of($this->start->getTokenIndex(), $this->start->getTokenIndex()-1); // empty
		}
		return Interval::of($this->start->getTokenIndex(), $this->stop->getTokenIndex());
	}

	/**
	 * Get the initial token in this context.
	 * Note that the range from start to stop is inclusive, so for rules that do not consume anything
	 * (for example, zero length or error productions) this token may exceed stop.
	 */
	public function getStart(): TokenInterface
    {
        return $this->start;
    }
	/**
	 * Get the final token in this context.
	 * Note that the range from start to stop is inclusive, so for rules that do not consume anything
	 * (for example, zero length or error productions) this token may precede start.
	 */
	public function getStop(): TokenInterface
    {
        return $this->stop;
    }

	/** Used for rule context info debugging during parse-time, not so much for ATN debugging */
	public function toInfoString(Parser $recognizer): string {
		$rules = $recognizer->getRuleInvocationStack($this);
		$rules = array_reverse($rules);
		return "ParserRuleContext"+$rules+"{" +
			"start=" + $this->start +
			", stop=" + $this->stop +
			'}';
	}
}

