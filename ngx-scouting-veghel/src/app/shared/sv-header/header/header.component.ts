import { Component, Input, OnInit } from '@angular/core';
import { SvMenuConfiguaration } from '../menu.interface';

@Component({
  selector: 'sv-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss'],
})
export class HeaderComponent {
  @Input() readonly menuConfiguation?: SvMenuConfiguaration;
}
