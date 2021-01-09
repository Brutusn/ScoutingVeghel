import { Component, Input, OnInit } from '@angular/core';
import { SvMenuConfiguaration } from '../menu.interface';

@Component({
  selector: 'sv-menu',
  templateUrl: './menu.component.html',
  styleUrls: ['./menu.component.scss'],
})
export class MenuComponent {
  @Input() readonly menuConfiguation: SvMenuConfiguaration = [];
}
