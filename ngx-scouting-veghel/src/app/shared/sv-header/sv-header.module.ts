import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeaderComponent } from './header/header.component';
import { MenuComponent } from './menu/menu.component';
import { RouterModule } from '@angular/router';



@NgModule({
  declarations: [HeaderComponent, MenuComponent],
  imports: [
    CommonModule,
    RouterModule,
  ],
  exports: [HeaderComponent]
})
export class SvHeaderModule { }
