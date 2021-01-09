import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HomePageComponent } from './home-page/home-page.component';
import { NotFoundComponent } from './not-found/not-found.component';

@NgModule({
  declarations: [HomePageComponent, NotFoundComponent],
  imports: [
    CommonModule
  ]
})
export class HomeModule { }
