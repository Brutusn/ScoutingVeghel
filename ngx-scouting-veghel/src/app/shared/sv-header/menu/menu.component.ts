import { ChangeDetectionStrategy, Component, Input, OnInit } from '@angular/core';
import { SvMenuConfiguaration } from '../menu.interface';

@Component({
  selector: 'sv-menu',
  templateUrl: './menu.component.html',
  styleUrls: ['./menu.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class MenuComponent {
  @Input() readonly menuConfiguation: SvMenuConfiguaration = [];
}
