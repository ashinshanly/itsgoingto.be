import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { hasQuestionSelector } from 'store/poll'
import { canSubmitPollSelector } from 'store/answers'
import { postPoll } from 'store/api'
import { browserHistory } from 'react-router'
import Button from 'components/Button/Button'

class Actions extends React.Component {
  submit = () => this.props.postPoll()
  .then((response) => {
    if (response !== false) {
      browserHistory.push('/react/' + response.identifier)
    }
  })

  render = () => (
    <div className={'actions hideable' + (this.props.hasQuestion ? '' : ' gone')}>
      <Button className='pull-right' text='Create Poll' disabled={!this.props.canSubmitPoll} callback={this.submit} />
    </div>
  )
}

Actions.propTypes = {
  hasQuestion   : PropTypes.bool.isRequired,
  canSubmitPoll : PropTypes.bool.isRequired,
  postPoll      : PropTypes.func.isRequired
}

const mapStateToProps = (state) => ({
  hasQuestion   : hasQuestionSelector(state),
  canSubmitPoll : canSubmitPollSelector(state)
})

const mapDispatchToProps = (dispatch) => ({
  postPoll : () => dispatch(postPoll())
})

export default connect(mapStateToProps, mapDispatchToProps)(Actions)
