import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ParentsPageComponent } from './parents-page.component';

describe('ParentsPageComponent', () => {
  let component: ParentsPageComponent;
  let fixture: ComponentFixture<ParentsPageComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ParentsPageComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ParentsPageComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
