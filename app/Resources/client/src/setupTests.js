import { configure } from 'enzyme'
import 'should'
import 'should-enzyme'
import Adapter from 'enzyme-adapter-react-15'

configure({ adapter: new Adapter() })