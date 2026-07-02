<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait PreventsBranchingLoops
{
    /**
     * Reject branching surveys whose option → next-question links form a cycle.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type', $this->route('survey')?->type);

            if ($type !== 'branching') {
                return;
            }

            if ($this->branchingHasLoop((array) $this->input('questions', []))) {
                $validator->errors()->add(
                    'questions',
                    'Branching cannot loop — an option leads back to a question already in the path.'
                );
            }
        });
    }

    /**
     * Detect a cycle in the directed graph of question → next-question edges.
     *
     * @param  array<int, mixed>  $questions
     */
    private function branchingHasLoop(array $questions): bool
    {
        $edges = [];   // question key => list of next-question keys
        $nodes = [];

        foreach (array_values($questions) as $index => $question) {
            $key = (string) ($question['key'] ?? $index);
            $nodes[$key] = true;
            $edges[$key] = [];

            foreach ((array) ($question['options'] ?? []) as $option) {
                $next = $option['next_key'] ?? null;
                if ($next !== null && $next !== '') {
                    $edges[$key][] = (string) $next;
                }
            }
        }

        $state = []; // 0 = unvisited, 1 = in current path, 2 = done

        $visit = function (string $node) use (&$visit, &$edges, &$state, &$nodes): bool {
            if (! isset($nodes[$node])) {
                return false; // edge to an unknown question → ignore
            }

            $state[$node] = 1;

            foreach ($edges[$node] as $next) {
                $next = (string) $next;
                $seen = $state[$next] ?? 0;

                if ($seen === 1) {
                    return true; // back-edge into the current path → cycle
                }

                if ($seen === 0 && $visit($next)) {
                    return true;
                }
            }

            $state[$node] = 2;

            return false;
        };

        foreach (array_keys($nodes) as $node) {
            if (($state[$node] ?? 0) === 0 && $visit((string) $node)) {
                return true;
            }
        }

        return false;
    }
}
