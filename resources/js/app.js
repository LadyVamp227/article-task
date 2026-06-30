import Alpine from 'alpinejs';

/**
 * Normalize a question coming from either the server (edit mode) or old() input
 * (after a validation error) into the shape the builder expects.
 */
function normalizeQuestion(q) {
    return {
        title: q.title ?? '',
        type: q.type ?? 'single_choice',
        is_required: q.is_required === true || q.is_required === '1' || q.is_required === 1,
        options: Array.isArray(q.options)
            ? q.options.map((o) => ({ label: o.label ?? '', value: o.value ?? '' }))
            : [],
    };
}

/**
 * Dynamic survey question/option builder used by the admin create/edit forms.
 */
Alpine.data('surveyForm', (initialQuestions = []) => ({
    questions: (initialQuestions || []).map(normalizeQuestion),

    isChoice(type) {
        return type === 'single_choice' || type === 'multiple_choice';
    },

    addQuestion() {
        this.questions.push({
            title: '',
            type: 'single_choice',
            is_required: false,
            // Seed one empty option since the default type is a choice type.
            options: [{ label: '', value: '' }],
        });
    },

    removeQuestion(index) {
        this.questions.splice(index, 1);
    },

    onTypeChange(index) {
        const question = this.questions[index];
        if (this.isChoice(question.type) && question.options.length === 0) {
            this.addOption(index);
        }
    },

    addOption(questionIndex) {
        this.questions[questionIndex].options.push({ label: '', value: '' });
    },

    removeOption(questionIndex, optionIndex) {
        this.questions[questionIndex].options.splice(optionIndex, 1);
    },
}));

window.Alpine = Alpine;
Alpine.start();
