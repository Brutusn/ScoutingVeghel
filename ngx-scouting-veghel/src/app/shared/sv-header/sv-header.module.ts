import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HeaderComponent } from './header/header.component';
import { MenuComponent } from './menu/menu.component';
import { RouterModule } from '@angular/router';
import { FooterComponent } from './footer/footer.component';

@NgModule({
  declarations: [HeaderComponent, MenuComponent, FooterComponent],
  imports: [
    CommonModule,
    RouterModule,
  ],
  exports: [HeaderComponent, FooterComponent]
})
export class SvHeaderModule { }
