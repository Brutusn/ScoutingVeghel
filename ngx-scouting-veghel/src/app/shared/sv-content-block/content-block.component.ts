import {
  Component,
  HostBinding,
  OnInit,
  ViewEncapsulation,
} from '@angular/core';

@Component({
  selector: 'sv-content-block',
  templateUrl: './content-block.component.html',
  styleUrls: ['./content-block.component.scss'],
  encapsulation: ViewEncapsulation.None,
})
export class SvContentBlockComponent {
  @HostBinding('class') readonly className = 'sv-content-block';
}
