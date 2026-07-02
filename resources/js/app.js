import Alpine from 'alpinejs';

// Monotonic client-side key for questions. Used by branching surveys so an
// option can point to another question that may not have a database id yet;
// the server maps these keys to real ids on save.
let questionKeySeq = 0;

function newKey() {
    return 'q' + ++questionKeySeq;
}

/**
 * Normalize a question coming from either the server (edit mode) or old() input
 * (after a validation error) into the shape the builder expects.
 */
function normalizeQuestion(q) {
    return {
        key: q.key ?? newKey(),
        title: q.title ?? '',
        type: q.type ?? 'single_choice',
        is_required: q.is_required === true || q.is_required === '1' || q.is_required === 1,
        options: Array.isArray(q.options)
            ? q.options.map((o) => ({ title: o.title ?? '', value: o.value ?? '', next_key: o.next_key ?? '' }))
            : [],
    };
}

/**
 * Dynamic survey question/option builder used by the admin create/edit forms.
 */
Alpine.data('surveyForm', (initialQuestions = [], initialType = 'linear') => ({
    type: initialType,
    questions: (initialQuestions || []).map(normalizeQuestion),

    init() {
        this.pruneStartTargets();
    },

    // No option may point back to the first question (that is always a loop).
    pruneStartTargets() {
        const startKey = this.questions[0]?.key;
        if (! startKey) {
            return;
        }
        this.questions.forEach((q) =>
            q.options.forEach((o) => {
                if (o.next_key === startKey) {
                    o.next_key = '';
                }
            })
        );
    },

    isChoice(type) {
        return type === 'single_choice' || type === 'multiple_choice';
    },

    isBranching() {
        return this.type === 'branching';
    },

    // Questions an option may branch to: everything except the option's own
    // question (self-loop) and the first question (jumping to the start loops).
    targetsFor(questionIndex) {
        return this.questions
            .map((q, index) => ({ key: q.key, index, title: q.title }))
            .filter((t) => t.index !== questionIndex && t.index !== 0);
    },

    addQuestion() {
        this.questions.push({
            key: newKey(),
            title: '',
            type: 'single_choice',
            is_required: false,
            // Seed one empty option since the default type is a choice type.
            options: [{ title: '', value: '', next_key: '' }],
        });
    },

    removeQuestion(index) {
        const removedKey = this.questions[index].key;
        this.questions.splice(index, 1);
        // Clear any option that pointed to the removed question.
        this.questions.forEach((q) =>
            q.options.forEach((o) => {
                if (o.next_key === removedKey) {
                    o.next_key = '';
                }
            })
        );
        // Removing the first question changes the start — re-prune.
        this.pruneStartTargets();
    },

    onTypeChange(index) {
        const question = this.questions[index];
        if (this.isChoice(question.type) && question.options.length === 0) {
            this.addOption(index);
        }
    },

    addOption(questionIndex) {
        this.questions[questionIndex].options.push({ title: '', value: '', next_key: '' });
    },

    removeOption(questionIndex, optionIndex) {
        this.questions[questionIndex].options.splice(optionIndex, 1);
    },
}));

/**
 * Public branching survey: reveals one question at a time (the next appears
 * below as soon as the current one is answered) and auto-saves progress.
 */
Alpine.data('surveyWizard', (questions = [], startId = null, saved = {}, saveUrl = '', thanksUrl = '', csrf = '') => ({
    q: {},
    startId,
    answers: {},
    visible: [],
    completed: false,
    saving: false,

    init() {
        questions.forEach((question) => {
            this.q[question.id] = question;
        });
        this.answers = { ...(saved || {}) };
        this.rebuild();
    },

    isChoice(type) {
        return type === 'single_choice' || type === 'multiple_choice';
    },

    answered(id) {
        const value = this.answers[id];
        if (this.q[id].type === 'multiple_choice') {
            return Array.isArray(value) && value.length > 0;
        }
        return value !== undefined && value !== null && value !== '';
    },

    nextOf(id) {
        const question = this.q[id];
        if (!this.isChoice(question.type)) {
            return null;
        }
        const selected = (question.type === 'multiple_choice' ? this.answers[id] || [] : [this.answers[id]]).map(Number);
        for (const option of question.options) {
            if (selected.includes(Number(option.id)) && option.next) {
                return option.next;
            }
        }
        return null;
    },

    // Recompute which questions are shown by walking the questions from the start,
    // following the answers given so far.
    rebuild() {
        this.visible = [];
        this.completed = false;
        let cur = this.startId;

        while (cur != null && this.q[cur]) {
            this.visible.push(cur);
            const question = this.q[cur];

            if (this.isChoice(question.type)) {
                if (!this.answered(cur)) {
                    return; // need this answer to know where to branch
                }
                cur = this.nextOf(cur);
            } else {
                // Textarea = terminal. A required one must be filled to finish.
                if (question.required && !this.answered(cur)) {
                    return;
                }
                this.completed = true;
                return;
            }
        }

        this.completed = true; // fell off the end → reached a terminal
    },

    onChange() {
        this.rebuild();
        this.save(false);
    },

    save(complete) {
        this.saving = true;
        return fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ answers: this.answers, completed: complete }),
        })
            .catch(() => {})
            .finally(() => {
                this.saving = false;
            });
    },

    submit() {
        this.save(true).then(() => {
            window.location = thanksUrl;
        });
    },
}));

window.Alpine = Alpine;
Alpine.start();
