import React from 'react'
import { browserHistory } from 'react-router'

class CatchAll extends React.Component {
  constructor (props) {
    super(props)
    browserHistory.push('/404')
  }

  render = () => (
    <div />
  )
}

export default CatchAll