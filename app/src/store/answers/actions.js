import { compose, not, length, omit, is, when, map, isNil } from 'ramda'

import { hasAnswerSelector, answersSelector, maxAnswerSelector } from 'store/answers/selectors'

// ------------------------------------
// Constants
// ------------------------------------
export const ANSWER_ADD = 'ANSWER_ADD'
export const ANSWER_UPDATE = 'ANSWER_UPDATE'
export const ANSWERS_UPDATE = 'ANSWERS_UPDATE'
export const ANSWER_REMOVE = 'ANSWER_REMOVE'
export const ANSWERS_REMOVE_AFTER = 'ANSWERS_REMOVE_AFTER'
export const ANSWERS_CLEAR = 'ANSWERS_CLEAR'

// ------------------------------------
// Actions
// ------------------------------------
/**
 * Add an answer to the state
 *
 * @return {object} dispatchable object
 */
export const addAnswer = () => ({
  type: ANSWER_ADD
})

/**
 * Update the answer in the state the dispatch to add or remove answers
 *
 * @param  {integer} index Answer index
 * @param  {String}  value Answer text
 *
 * @return {Function}      redux-thunk callable function
 */
export const updateAnswer = (index, value = '') => (dispatch, getState) => {
  const hadAnswer = hasAnswerSelector(getState(), index)

  dispatch({
    type: ANSWER_UPDATE,
    index,
    text: value
  })
  const hasAnswer = hasAnswerSelector(getState(), index)
  const countAnswers = length(answersSelector(getState()))

  if (index === (countAnswers - 1) && hasAnswer && !hadAnswer) {
    dispatch(addAnswer())
  } else if (hadAnswer && !hasAnswer) {
    dispatch(removeAfterAnswer(maxAnswerSelector(getState()) + 1))
  }

  return Promise.resolve()
}

/**
 * Update all the answers in the state
 *
 * @param  {array}  answers Array of answers
 *
 * @return {object}         dispatchable object
 */
export const updateAnswers = (answers = []) => ({
  type: ANSWERS_UPDATE,
  answers: when(
    compose(not, isNil),
    map(
      when(
        is(Object),
        omit(['poll'])
      ),
    )
  )(answers)
})

/**
 * Remove an answer at the index
 *
 * @param  {integer} index Index of the answer
 *
 * @return {object}         dispatchable object
 */
export const removeAnswer = (index) => ({
  type: ANSWER_REMOVE,
  index
})

/**
 * Remove answers after the index
 *
 * @param  {integer} index Index after which to remove answers
 *
 * @return {object}         dispatchable object
 */
export const removeAfterAnswer = (index) => ({
  type: ANSWERS_REMOVE_AFTER,
  index
})

/**
 * Clear all the answers from the state
 *
 * @return {object}         dispatchable object
 */
export const clearAnswers = () => ({
  type: ANSWERS_CLEAR
})
